#include "auth.h"

struct Auth* auth_new_user(struct Auth *this)
{
	auth_save(this);
	return this;
}

bool auth_load(struct Auth *this)
{
	FILE *file = fs_load_user(this->username);
	if (file == NULL)
		return false;

	fscanf(file, "%s %s", this->username, this->password);
	fclose(file);
	return true;
}

bool auth_login(struct Auth *this, char *password)
{
	if (strcmp(this->password, password) == 0)
		this->authenticated = true;
	return this->authenticated;
}

void auth_save(struct Auth *this)
{
	char data[128];
	snprintf(data, sizeof(data), "%s %s", this->username, this->password);

	fs_save_user(this->username, data);
}
