#include "main.h"

int main()
{
	setvbuf(stdout, NULL, _IONBF, 0);
	setvbuf(stdin, NULL, _IONBF, 0);
	setvbuf(stderr, NULL, _IONBF, 0);

	struct Auth auth;
	char password[64];

	printf("Welcome to the notary! Please log in.\n");
	printf("Username: ");
	scanf("%s", auth.username);
	printf("Password: ");
	scanf("%s", password);

	if (auth_load(&auth))
	{
		printf("User found. Logging in...\n");
		if (auth_login(&auth, password) == false)
		{
			printf("Invalid password!\n");
			return 1;
		}
	}
	else
	{
		strcpy(auth.password, password);
		printf("User not found. Creating new user...\n");
		auth_new_user(&auth);
		printf("New user created.\n");
	}

	cli_start(&auth);

	return 0;
}
