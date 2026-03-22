#ifndef APP_AUTH_H
#define APP_AUTH_H

#include "fs.h"

#include <stdio.h>
#include <string.h>
#include <stdbool.h>

struct Auth
{
	char username[64];
	char password[64];
	bool authenticated;
};

struct Auth* auth_new_user(struct Auth *this);
bool auth_load(struct Auth *this);
bool auth_login(struct Auth *this, char *password);
void auth_save(struct Auth *this);

#endif
