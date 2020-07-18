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

#define RESPONSE_LEN 65536

struct printer_data{
  libusb_device_handle *handle;
  uint8_t config;
  uint8_t interface;
  uint8_t endpoint_in;
  uint8_t endpoint_out;
  uint8_t max_packet_size;
} typedef printer_data;

printer_data printerData;
SSL_CTX *sslContext = NULL;
BIO *bio = NULL;


// returns 1 if everything sent okay, 0 otherwise.
int SendDataToPrinter(unsigned char *buffer, unsigned int length){
  int success = 1;
  int bytesTransferred;
  unsigned int transferError;
  int amountLeftToTransfer = length;

  // TODO(Trystan): Check for printerData.max_packet_size, and break down our transfers.
  while(amountLeftToTransfer){
    int amountToTransfer = printerData.max_packet_size;
    if(amountToTransfer > amountLeftToTransfer){
      amountToTransfer = amountLeftToTransfer;
    }

    transferError = libusb_bulk_transfer(printerData.handle, printerData.endpoint_out,
						      buffer, amountToTransfer, &bytesTransferred, 0);

    if(transferError == 0){
      if(amountToTransfer != bytesTransferred){
	syslog(LOG_NOTICE, "Bytes (%d) were not transferred entirely (%d).\n", amountToTransfer, bytesTransferred);
	success = 0;
      }
    } else {
      syslog(LOG_NOTICE, "Error when calling bulk transfer(%d).\n", transferError);
      success = 0;
    }

    amountLeftToTransfer -= amountToTransfer;
    buffer += amountToTransfer;
  }

  return success;
}

void ClearString(char *string){
  int length = strlen(string);
  for(int i = 0; i < length; i++){
    string[i] = 0;
  }
}

void openStream(char *token, char *commonHeaders){
  if(bio == NULL) return;
  syslog(LOG_NOTICE,  "Opening stream.\n");

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

// returns 1 if printer was found, and sets relevant value in printerData.
// doesn't touch printerData if 0 is returned.
int isDevicePrinter(libusb_device *device){
  int isPrinter = 0;

  struct libusb_device_descriptor deviceDescription;
  libusb_get_device_descriptor(device, &deviceDescription);

  struct libusb_config_descriptor *configDescription;
  libusb_get_config_descriptor(device, 0, &configDescription);
    
  for(int i = 0; i < configDescription->bNumInterfaces; i++){
    const struct libusb_interface interface = configDescription->interface[i];

    for(int j = 0; j < interface.num_altsetting; j++){
      // check that the interface descriptor matches LIBUSB_CLASS_PRINTER
      const struct libusb_interface_descriptor interfaceDescriptor = interface.altsetting[j];

      if(interfaceDescriptor.bInterfaceClass == LIBUSB_CLASS_PRINTER){
	// This is the device we want, begin gathering all necessary information.
	// iterate over end points. Grab endpoint_out, and endpoint_in if there is one.
	isPrinter = 1;
	
	printerData.max_packet_size = deviceDescription.bMaxPacketSize0;
	printerData.config = configDescription->bConfigurationValue;
	printerData.interface = interfaceDescriptor.bInterfaceNumber;

	for(int k = 0; k < interfaceDescriptor.bNumEndpoints; k++){
	  const struct libusb_endpoint_descriptor endpointDescriptor = interfaceDescriptor.endpoint[k];

	  // check to see what type of endpoint. if 8th bit is 0 = out, 1 = in
	  if(endpointDescriptor.bEndpointAddress & (1 << 7)){
	    printerData.endpoint_in = endpointDescriptor.bEndpointAddress;
	  } else {
	    printerData.endpoint_out = endpointDescriptor.bEndpointAddress;
	  }
	}
      }
    }
  }

  libusb_free_config_descriptor(configDescription);

  return isPrinter;
}

void connectPrinter(libusb_device *device){
  syslog(LOG_NOTICE, "Printer attached.\n");
  int error = libusb_open(device, &printerData.handle);

  if(error){
    if(error == -3){
      syslog(LOG_NOTICE, "Incorrect permissions. Unable to get a handle to the printer.\n");
    } else {
      syslog(LOG_NOTICE, "Unable to get a handle to the printer - error code: %d\n", error);
    }
  }

  error = 1;
  while(error != LIBUSB_SUCCESS){
    error = libusb_set_configuration(printerData.handle, printerData.config);
    if(error){
      syslog(LOG_NOTICE, "Could not set the configuration(%d) error(%d)\n", printerData.config, error);
      if(error == LIBUSB_ERROR_NOT_FOUND || error == LIBUSB_ERROR_NO_DEVICE){
	syslog(LOG_NOTICE, "Configuration set aborted, device missing.\n");
	break;
      } else {
	syslog(LOG_NOTICE, "retrying to set configuration.\n");
      }
    } else {
      syslog(LOG_NOTICE, "successfully set the configuration(%d)\n", printerData.config);
    }
  }

  if(libusb_kernel_driver_active(printerData.handle, printerData.interface) == 1){
    error = libusb_detach_kernel_driver(printerData.handle, printerData.interface);
    if(error){
      syslog(LOG_NOTICE, "Could not detach kernal driver error(%d)\n", error);
    }
  }

  error = libusb_claim_interface(printerData.handle, printerData.interface);
  if(error != 0){
    syslog(LOG_NOTICE, "Unable to claim interface(%d) error(%d)\n", printerData.interface, error);
  }
}

void disconnectPrinter(){
  syslog(LOG_NOTICE, "Printer detached. Please reconnect.\n");
  int error;
  // This will always be an error when called from the hotplug as the device is missing.
  error = libusb_release_interface(printerData.handle, printerData.interface);
  if(error){
    syslog(LOG_NOTICE, "release interface error(%d)\n", error);
  }

  // No errors associated with closing the device.
  libusb_close(printerData.handle);
  
  printerData.handle = NULL;
  printerData.endpoint_in = 0;
  printerData.endpoint_out = 0;
  printerData.max_packet_size = 0;
}

static int LIBUSB_CALL hotplug_callback(libusb_context *context, libusb_device *device,
					libusb_hotplug_event event, void *user_data){
  if(!isDevicePrinter(device)){
    return 0;
  }

  if(event == LIBUSB_HOTPLUG_EVENT_DEVICE_ARRIVED){
    connectPrinter(device);
  } else if (event == LIBUSB_HOTPLUG_EVENT_DEVICE_LEFT){
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

  printerData.handle = NULL;
  printerData.endpoint_in = 0;
  printerData.endpoint_out = 0;
  printerData.max_packet_size = 0;

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
  libusb_hotplug_register_callback(NULL, LIBUSB_HOTPLUG_EVENT_DEVICE_ARRIVED | LIBUSB_HOTPLUG_EVENT_DEVICE_LEFT,
				   0, LIBUSB_HOTPLUG_MATCH_ANY, LIBUSB_HOTPLUG_MATCH_ANY, LIBUSB_HOTPLUG_MATCH_ANY,
				   hotplug_callback, NULL, &callback_handle);

  libusb_device **devs;
  libusb_get_device_list(NULL, &devs);

  libusb_device *dev;
  libusb_device *printerDevice = NULL;
  int devsIndex = 0;
  while ((dev = devs[devsIndex++]) != NULL) {
    if(isDevicePrinter(dev)){
      printerDevice = dev;
    }
  }

  libusb_free_device_list(devs, 1);

  if(printerDevice){
    connectPrinter(printerDevice);
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

    if(printerData.handle){
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

	int textLength = strlen(orderBegin);
	char *printerText = (char *)malloc((textLength) + 1);
	memcpy(printerText, orderBegin, textLength);
	printerText[textLength] = '\0';

	if(printerText){
	  // TODO(Trystan): Not sure of the safety of sending all unfiltered data
	  // directly to the printer. But so far I can't think of any major vulnerabilities.
	  // Other than someone being able to run through a whole spool of receipt paper.
	  // If they were able to send the right command, which I was not able to do so.
	  if(SendDataToPrinter(printerText, textLength)){
	    FILE *dateFile = fopen("orderDate.txt", "w");
	    fputs(lastOrderDate, dateFile);
	    fclose(dateFile);
	  }
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
