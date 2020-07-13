// (C) Copyright 2020 by Trystan Brock All Rights Reserved.

#include <stdio.h>
// TODO(trystan): Make sure we actually need this one.
#include <stdlib.h>
// TODO(trystan): We want to be able to remove this at some point.
#include <string.h>
// SUGGESTION(trystan): strip out the functionality we actually need
// from libusb and insert it here. Learn how it grabs the usb devices.
#include <libusb.h>

// Network related includes.
#include <sys/types.h>
#include <sys/socket.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <poll.h>

#define VENDOR_ID 0x0416
#define PRODUCT_ID 0x5011
#define ENDPOINT_IN 0x81
#define ENDPOINT_OUT 0x01

#define RESPONSE_LEN 65536

// TODO(trystan):
// hotplug support
// Bring everything that can fail the program to the top of the program
//  - Attempt to open files at the top.

// Check if(printerHandle) to see if it's connected or not.
libusb_device_handle *printerHandle = NULL;
int sockfd = -1;

void SendDataToPrinter(libusb_device_handle *handle, unsigned char *buffer, unsigned int length){
  int bytesTransferred;
  
  unsigned int transferError = libusb_bulk_transfer(handle, ENDPOINT_OUT,
						    buffer, length, &bytesTransferred, 0);
  if(transferError == 0){
    if(length == bytesTransferred){
      
    } else {
      printf("Bytes (%d) were not transferred entirely (%d).\n", length, bytesTransferred);
    }
  } else {
    printf("Error when calling bulk transfer(%d).\n", transferError);
  }
}

void ClearString(char *string){
  int length = strlen(string);
  for(int i = 0; i < length; i++){
    string[i] = 0;
  }
}

void openStream(char *token, char *commonHeaders){
  if(sockfd == -1) return;
  printf("Opening stream.\n");

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
  send(sockfd, requestStream, strlen(requestStream), 0);
}

void connectServer(char *host){
  printf("Opening connection to the server.\n");
  struct addrinfo hints, *addrResults;
  memset(&hints, 0, sizeof hints);
  hints.ai_family = AF_INET;
  hints.ai_socktype = SOCK_STREAM;

  int result = getaddrinfo(host, "http", &hints, &addrResults);

  char ipstr[INET6_ADDRSTRLEN];
  
  for(struct addrinfo *addrResult = addrResults; addrResult != NULL; addrResult = addrResult->ai_next){
    struct sockaddr_in *ipv4 = (struct sockaddr_in *)addrResult->ai_addr;

    void *addr = &(ipv4->sin_addr);

    inet_ntop(addrResult->ai_family, addr, ipstr, sizeof ipstr);
  }

  // TODO(trystan): This code assumes the first result is the correct one.
  // That might not always be true, do some checking up above and actually set the value.
  struct addrinfo *our_address = addrResults;
    

  sockfd = socket(our_address->ai_family, our_address->ai_socktype, our_address->ai_protocol);

  result = connect(sockfd, our_address->ai_addr, our_address->ai_addrlen);


  freeaddrinfo(addrResults);

  if(result == -1){
    printf("connect call has failed.\n");
    sockfd = -1;
  }
}

void disconnectServer(){
  printf("Closing connection to the server.\n");

  close(sockfd);
  sockfd = -1;
}

void connectPrinter(libusb_device *device){
  int open_error = libusb_open(device, &printerHandle);

  if(open_error){
    if(open_error == -3){
      printf("Incorrect permissions. Unable to get a handle to the printer.\n");
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
    printf("Printer attached.\n");
    connectPrinter(device);
  } else if (event == LIBUSB_HOTPLUG_EVENT_DEVICE_LEFT){
    printf("Printer detached. Please reconnect.\n");
    disconnectPrinter();
    disconnectServer();
  }
  
  return 0;
}

int main(int argc, char **argv){
  char *host = argv[1];
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
    printf("Printer not connected.\n");
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
      if(sockfd == -1){
	connectServer(host);
	openStream(token, commonHeaders);
	if(sockfd == -1){
	  // Try to reconnect every 10 seconds.
	  printf("Retrying connection in 10 seconds.\n");
	  sleep(10);
	  continue;
	}
      }
      int received = recv(sockfd, response, RESPONSE_LEN, 0);
      printf("%s", response);
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
      }
    }
  }

  disconnectServer();
  disconnectPrinter();

  libusb_exit(NULL);
  
  return 0;
}
