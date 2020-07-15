// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <signal.h>
#include <syslog.h>
#include <string.h>

#include <libusb-1.0/libusb.h>
#include <openssl/bio.h>
#include <openssl/ssl.h>
#include <openssl/err.h>

#define VENDOR_ID 0x0416
#define PRODUCT_ID 0x5011
#define ENDPOINT_IN 0x81
#define ENDPOINT_OUT 0x01

#define RESPONSE_LEN 65536

// TODO(trystan):
// hotplug support
// Bring everything that can fail the program to the top of the program
//  - Attempt to open files at the top.

libusb_device_handle *printerHandle = NULL;
SSL_CTX *sslContext = NULL;
BIO *bio = NULL;

void SendDataToPrinter(libusb_device_handle *handle, unsigned char *buffer, unsigned int length){
  int bytesTransferred;
  
  unsigned int transferError = libusb_bulk_transfer(handle, ENDPOINT_OUT,
						    buffer, length, &bytesTransferred, 0);
  if(transferError == 0){
    if(length == bytesTransferred){
      
    } else {
      syslog(LOG_NOTICE, "Bytes (%d) were not transferred entirely (%d).\n", length, bytesTransferred);
    }
  } else {
    syslog(LOG_NOTICE, "Error when calling bulk transfer(%d).\n", transferError);
  }
}

void ClearString(char *string){
  int length = strlen(string);
  for(int i = 0; i < length; i++){
    string[i] = 0;
  }
}

void openStream(char *token, char *commonHeaders){
  if(bio == NULL) return;
  syslog(LOG_NOTICE, "Opening stream.\n");

  // Date is in YYYY-MM-DD hh:mm:ss
  // When we save to the file we have to replace the space with a %20,
  // so that way when we send the date to the server it's in url format.
  char *line = NULL;
  size_t lineLength = 0;
  ssize_t read;
  
  FILE *dateFile = fopen("orderDate.txt", "r");
  char lastOrderDate[22];
  memset(lastOrderDate, 0, 22);
  if(dateFile){
    read = getline(&line, &lineLength, dateFile);
    if(read != -1){
      strncpy(lastOrderDate, line, read - 1);
      lastOrderDate[read] = '\0';
    }
    fclose(dateFile);
  } else {
    // No date file found. This would mean we'd print every active order.
    strcpy(lastOrderDate, "NULL");
  }
  
  char payload[512];
  memset(payload, 0, 512);
  sprintf(payload, "token=%s&lastReceived=%s", token, lastOrderDate);

  int payloadSize = strlen(payload);

  char requestStream[2048];
  memset(requestStream, 0, 2048);
  sprintf(requestStream, "POST /Dashboard/orders/printerStream HTTP/1.1\r\n%s\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: %d\r\n\r\n%s", commonHeaders, payloadSize, payload);
  int writeResult = BIO_write(bio, requestStream, strlen(requestStream));
  if(writeResult <= 0){
    if(!BIO_should_retry(bio)){
      // handle failed write
      // possibly open new connection.
    }

    // safe to retry.
  }
}

void disconnectServer(){
  syslog(LOG_NOTICE, "Closing connection to the server.\n");

  BIO_free_all(bio);
  bio = NULL;
}

void connectServer(SSL *ssl, char *host){
  syslog(LOG_NOTICE, "Opening connection to the server.\n");

  if(bio == NULL){
    bio = BIO_new_ssl_connect(sslContext);
    syslog(LOG_NOTICE, "Bio setup\n");
    BIO_get_ssl(bio, &ssl);
    syslog(LOG_NOTICE, "ssl setup\n");
    SSL_set_mode(ssl, SSL_MODE_AUTO_RETRY);
    // NOTE(Trystan): host must be appended with :port
    BIO_set_conn_hostname(bio, host);
    
    if(BIO_do_connect(bio) <= 0){
      disconnectServer();
      return;
    }

    if(SSL_get_verify_result(ssl) != X509_V_OK){
      disconnectServer();
      return;
    }
  }
}

void connectPrinter(libusb_device *device){
  int open_error = libusb_open(device, &printerHandle);

  if(open_error){
    if(open_error == -3){
      syslog(LOG_NOTICE, "Incorrect permissions. Unable to get a handle to the printer.\n");
    } else {
      syslog(LOG_NOTICE, "Unable to get a handle to the printer - error code: %d\n", open_error);
    }
  }

  if(libusb_kernel_driver_active(printerHandle, 0) == 1){
    libusb_detach_kernel_driver(printerHandle, 0);
  }

  libusb_claim_interface(printerHandle, 1);
}

void disconnectPrinter(){
  libusb_release_interface(printerHandle, 0);
  libusb_close(printerHandle);
  printerHandle = NULL;
}

static int LIBUSB_CALL hotplug_callback(libusb_context *context, libusb_device *device,
					libusb_hotplug_event event, void *user_data){
  struct libusb_device_descriptor descriptor;

  libusb_get_device_descriptor(device, &descriptor);

  if(event == LIBUSB_HOTPLUG_EVENT_DEVICE_ARRIVED){
    syslog(LOG_NOTICE, "Printer attached.\n");
    connectPrinter(device);
  } else if (event == LIBUSB_HOTPLUG_EVENT_DEVICE_LEFT){
    syslog(LOG_NOTICE, "Printer detached. Please reconnect.\n");
    disconnectPrinter();
    disconnectServer();
  }
  
  return 0;
}

void signalHandler(int signal) {
  switch(signal){
  case SIGKILL:
  case SIGTERM:
    // daemon has been killed, cleanup.
    closelog();
    disconnectServer();
    disconnectPrinter();
    
    libusb_exit(NULL);
    SSL_CTX_free(sslContext);
    exit(0);
    break;
  default:
    break;
  }
}

int main(int argc, char **argv){
  char *host = argv[1];

  // turn it into a daemon, then continue.
  setlogmask(LOG_UPTO(LOG_NOTICE));
  openlog("orderprinterd", LOG_CONS | LOG_NDELAY | LOG_PERROR | LOG_PID, LOG_USER);

  pid_t pid, sid;

  pid = fork();
  if(pid < 0){
    return -1;
  }

  if(pid > 0){
    // Succesfully forked, close the parent process.
    return 0;
  }

  umask(0);

  sid = setsid();
  if(sid < 0){
    return -1;
  }

  if((chdir("/etc/orderprinterd")) < 0){
    syslog(LOG_NOTICE, "couldn't change dir to /etc/orderprinterd\n");
    return -1;
  }

  SSL_load_error_strings();
  ERR_load_BIO_strings();
  OpenSSL_add_all_algorithms();

  sslContext = SSL_CTX_new(SSLv23_client_method());
  SSL *ssl;

  if(!SSL_CTX_load_verify_locations(sslContext, "cacert.pem", NULL)){
    syslog(LOG_NOTICE, "Failed to load cert file.\n");
  }
  
  libusb_init(NULL);

  libusb_hotplug_callback_handle callback_handle;
  libusb_hotplug_register_callback(NULL, LIBUSB_HOTPLUG_EVENT_DEVICE_ARRIVED | LIBUSB_HOTPLUG_EVENT_DEVICE_LEFT, 0, VENDOR_ID,
				   PRODUCT_ID, LIBUSB_HOTPLUG_MATCH_ANY, hotplug_callback, NULL, &callback_handle);
  
  libusb_device **devs;
  libusb_get_device_list(NULL, &devs);

  libusb_device *dev;
  libusb_device *printer = NULL;
  int devsIndex = 0;
  while ((dev = devs[devsIndex++]) != NULL) {
    struct libusb_device_descriptor description;
    libusb_get_device_descriptor(dev, &description);

    if(description.idVendor == VENDOR_ID && description.idProduct == PRODUCT_ID){
      printer = dev;
    }
  }

  libusb_free_device_list(devs, 1);

  if(printer){
    connectPrinter(printer);
  } else {
    syslog(LOG_NOTICE, "Printer not connected.\n");
  }

  // TODO(trystan): Look into necessary headers to add on to here.
  char commonHeaders[512];
  memset(commonHeaders, 0, 512);
  sprintf(commonHeaders, "Host: %s\r\nConnection: keep-alive", host);
  
  FILE *tokenFile = fopen("token.txt", "r");
  char *line = NULL;
  size_t lineLength = 0;
  ssize_t read;
  char token[256];
  memset(token, 0, 256);
  read = getline(&line, &lineLength, tokenFile);
  if(read != -1){
    strncpy(token, line, read);
    token[read] = '\0';
  }

  char response[RESPONSE_LEN];
  memset(response, 0, RESPONSE_LEN);

  struct timeval zeroTimeValue;
  zeroTimeValue.tv_usec = 0;
  zeroTimeValue.tv_sec = 0;

  // Finally the moment we've all been waiting for.
  for(;;){
    libusb_handle_events_timeout_completed(NULL, &zeroTimeValue, NULL);

    if(printerHandle){
      if(bio == NULL){
	connectServer(ssl, host);
	openStream(token, commonHeaders);
	if(bio == NULL){
	  // Try to reconnect every 10 seconds.
	  syslog(LOG_NOTICE, "Retrying connection in 10 seconds.\n");
	  sleep(10);
	  continue;
	}
      }
      int received = BIO_read(bio, response, RESPONSE_LEN);
      char *orderBegin = strstr(response, "TIMESTAMP");
      if(orderBegin){
	char lastOrderDate[22];
	memset(lastOrderDate, 0, 22);
	
	int offset = 0;
	for(int i = 0; i < 20; i++){
	  if(orderBegin[i+10] != ' '){
	    lastOrderDate[i + offset] = orderBegin[i+10];
	  } else {
	    // Instead of space, write %20
	    lastOrderDate[i] = '%';
	    offset++;
	    lastOrderDate[i+offset] = '2';
	    offset++;
	    lastOrderDate[i+offset] = '0';
	  }
	}
	FILE *dateFile = fopen("orderDate.txt", "w");
	fputs(lastOrderDate, dateFile);
	fclose(dateFile);

	int textLength = strlen(orderBegin);
	char *printerText = (char *)malloc((textLength) + 1);
	memcpy(printerText, orderBegin, textLength);
	printerText[textLength] = '\0';

	if(printerText){
	  // TODO(Trystan): Not sure of the safety of sending all unfiltered data
	  // directly to the printer. But so far I can't think of any major vulnerabilities.
	  // Other than someone being able to run through a whole spool of receipt paper.
	  // If they were able to send the right command, which I was not able to do so.
	  SendDataToPrinter(printerHandle, printerText, textLength);
	}
	free(printerText);
      }

      ClearString(response);
      if(received == 0){
	disconnectServer();
      } else if(received < 0){
	if(!BIO_should_retry(bio)){
	  // handle failed read here.
	  
	}

	// retry read.
      }
    }
  }

  return 0;
}
