#include <stdio.h>
// TODO(trystan): Make sure we actually need this one.
#include <stdlib.h>
// TODO(trystan): We want to be able to remove this at some point.
#include <string.h>
// SUGGESTION(trystan): strip out the functionality we actually need
// from libusb and insert it here. Learn how it grabs the usb devices.
#include <libusb-1.0/libusb.h>

// Network related includes.
#include <sys/types.h>
#include <sys/socket.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <unistd.h>

#define VENDOR_ID 0x0416
#define PRODUCT_ID 0x5011
#define ENDPOINT_IN 0x81
#define ENDPOINT_OUT 0x01

#define HOST "127.0.0.1"
#define RESPONSE_LEN 65536

// TODO(trystan):
// hotplug support
// Bring everything that can fail the program to the top of the program
//  - Attempt to open files at the top.

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

int main(){
  libusb_device **devs;
  int r;
  ssize_t cnt;

  r = libusb_init(NULL);
  if (r < 0)
    return r;

  cnt = libusb_get_device_list(NULL, &devs);
  if (cnt < 0){
    libusb_exit(NULL);
    return (int) cnt;
  }

  libusb_device *dev;
  libusb_device *printer = NULL;
  int i = 0;
  while ((dev = devs[i++]) != NULL) {
    struct libusb_device_descriptor description;
    int result = libusb_get_device_descriptor(dev, &description);
    if (result < 0){
      fprintf(stderr, "failed to get device descriptor");
      return (int) result;
    }

    if(description.idVendor == VENDOR_ID && description.idProduct == PRODUCT_ID){
      printer = dev;
    }
  }

  libusb_free_device_list(devs, 1);

  if(!printer){
    printf("printer not found.\n");
  }

  libusb_device_handle *printerHandle;
  int open_error = libusb_open(printer, &printerHandle);

  if(open_error){
    printf("Unable to get a handle to the printer.\n");
  }

  // This part is never going to be true for the cheap chinese printer.
  if(libusb_kernel_driver_active(printerHandle, 0) == 1){
    libusb_detach_kernel_driver(printerHandle, 0);
  }

  libusb_claim_interface(printerHandle, 1);

  struct addrinfo hints, *addrResults;
  memset(&hints, 0, sizeof hints);
  hints.ai_family = AF_INET;
  hints.ai_socktype = SOCK_STREAM;

  // TODO(trystan): At the moment while running on this computer,
  // I assume the address is localhost
  // Obviously at some point we will want to switch over to a live website.
  // Point this to tonys.trystanbrock.dev when we get set up on digital ocean.
  int result = getaddrinfo("localhost", "http", &hints, &addrResults);

  char ipstr[INET6_ADDRSTRLEN];
  
  for(struct addrinfo *addrResult = addrResults; addrResult != NULL; addrResult = addrResult->ai_next){
    struct sockaddr_in *ipv4 = (struct sockaddr_in *)addrResult->ai_addr;
    void *addr = &(ipv4->sin_addr);

    inet_ntop(addrResult->ai_family, addr, ipstr, sizeof ipstr);
    //printf("   %s\n", ipstr);
  }

  // TODO(trystan): This code assumes the first result is the correct one.
  // That might not always be true, do some checking up above and actually set the value.
  struct addrinfo *our_address = addrResults;
  int sockfd = socket(our_address->ai_family, our_address->ai_socktype, our_address->ai_protocol);

  result = connect(sockfd, our_address->ai_addr, our_address->ai_addrlen);

  if(result == -1){
    printf("connect call has failed.");
  }

  // structure of our requests:
  // we should authenticate the receipt user on the server
  // request the login page
  // TODO(trystan): Look into necessary headers to add on to here.
  char commonHeaders[512];
  memset(commonHeaders, 0, 512);
  sprintf(commonHeaders, "Host: %s\r\nConnection: keep-alive", HOST);
  
  char getLogin[1024];
  memset(getLogin, 0, 1024);
  sprintf(getLogin, "GET /Login HTTP/1.1\r\n%s\r\n\r\n", commonHeaders);
  send(sockfd, getLogin, strlen(getLogin), 0);
  char response[RESPONSE_LEN];
  memset(response, 0, RESPONSE_LEN);
  recv(sockfd, response, RESPONSE_LEN, 0);

  // parse the CSRFToken out of the response along with the PHP session-id
  char *substring = strstr(response, "CSRFToken");
  
  substring = strstr(substring, "value");
  char CSRFToken[65];
  memset(CSRFToken, 0, 65);
  for(int i = 0; i < 64; i++){
    // 7 is the offset from the beggining of value to the first character of the token.
    // 0|value="|7
    CSRFToken[i] = substring[i+7];
  }
  substring[0] = '\0';
  substring = strstr(response, "tonys_session_id");
  char sessionID[27];
  memset(sessionID, 0, 27);
  for(int i = 0; i < 26; i++){
    sessionID[i] = substring[i+10];
  }
  
  ClearString(commonHeaders);
  sprintf(commonHeaders, "Host: %s\r\nConnection: keep-alive\r\nCookie: tonys_session_id=%s", HOST, sessionID);

  FILE *authFile = fopen("auth.txt", "r");
  char *line = NULL;
  size_t lineLength = 0;
  ssize_t read;
  char email[128];
  memset(email, 0, 128);
  read = getline(&line, &lineLength, authFile);
  if(read != -1){
    // We don't want to copy the '\n' char.
    // We then have to terminate the string ourselves.
    strncpy(email, line, read - 1);
    email[read] = '\0';
  }

  char password[128];
  memset(password, 0, 128);
  read = getline(&line, &lineLength, authFile);
  if(read != -1){
    strncpy(password, line, read - 1);
    password[read] = '\0';
  }

  char payload[512];
  memset(payload, 0, 512);
  sprintf(payload, "email=%s&password=%s&CSRFToken=%s", email, password, CSRFToken);
  int payloadSize = strlen(payload);
  char postLogin[2048];
  memset(postLogin, 0, 2048);
  sprintf(postLogin, "POST /Login HTTP/1.1\r\n%s\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: %d\r\n\r\n%s", commonHeaders, payloadSize, payload);
  send(sockfd, postLogin, strlen(postLogin), 0);

  ClearString(response);
  
  recv(sockfd, response, RESPONSE_LEN, 0);
  // TODO(trystan): Perhaps some sort of checking if we successfully logged in.
  // Or do that check to see if we can't get the printerStream.
  ClearString(response);
  // At this point we are logged in and ready to visit the api.
  
  // Date is in YYYY-MM-DD hh:mm:ss
  // When we save to the file we have to replace the space with a %20,
  // so that way when we send the date to the server it's in url format.
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

  char getDashboard[1024];
  memset(getDashboard, 0, 1024);
  sprintf(getDashboard, "GET /Dashboard/orders/printerStream?lastReceived=%s HTTP/1.1\r\n%s\r\n\r\n", lastOrderDate, commonHeaders);
  send(sockfd, getDashboard, strlen(getDashboard), 0);

  FILE *delimiterFile = fopen("delimiter.txt", "r");
  char delimiter[128];
  memset(delimiter, 0, 128);
  if(delimiterFile){
    read = getline(&line, &lineLength, delimiterFile);
    if(read != -1){
      strncpy(delimiter, line, read - 1);
      delimiter[read] = '\0';
    }
    fclose(delimiterFile);
  } else {
    // FATAL ERROR
    printf("delimiter.txt not found, please contact Trystan for help.\n");
    return -1;
  }

  int delimiterLength = strlen(delimiter);
  //char *newDate;
  ClearString(lastOrderDate);
  // Finally the moment we've all been waiting for.
  for(;;){
    int received = recv(sockfd, response, RESPONSE_LEN, 0);
    // TODO(trystan): Take the reponse and parse out the info between the delimeters.
    char *begin = strstr(response, delimiter);
    begin += delimiterLength;

    char *end = strstr(begin, delimiter);
    int textLength = end - begin;
    
    char *printerText = (char *)malloc((textLength) + 1);
    memcpy(printerText, begin, textLength);
    printerText[textLength] = '\0';

    if(printerText){
      // TODO(Trystan): Not sure of the safety of sending all unfiltered data
      // directly to the printer. But so far I can't think of any major vulnerabilities.
      // Other than someone being able to run through a whole spool of receipt paper.
      // If they were able to send the right command, which I was not able to do so.
      SendDataToPrinter(printerHandle, printerText, textLength);
    }

    free(printerText);

    end += delimiterLength;
    char *newDate = strstr(end, "TIMESTAMP");
    if(newDate){
      int offset = 0;
      for(int i = 0; i < 22; i++){
	if(newDate[i+10] != ' '){
	  lastOrderDate[i + offset] = newDate[i+10];
	} else {
	  // Instead of space, write %20
	  lastOrderDate[i] = '%';
	  offset++;
	  lastOrderDate[i+offset] = '2';
	  offset++;
	  lastOrderDate[i+offset] = '0';
	}
      }
      dateFile = fopen("orderDate.txt", "w");
      fputs(lastOrderDate, dateFile);
      fclose(dateFile);
      ClearString(lastOrderDate);
    }

    ClearString(response);
    if(received == 0){
      break;
    }
  }

  close(sockfd);
  
  freeaddrinfo(addrResults);
  
  libusb_release_interface(printerHandle, 0);
  
  libusb_close(printerHandle);
  libusb_exit(NULL);
  return 0;
}
