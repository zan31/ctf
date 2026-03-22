#include "cli.h"

const int MAX_FILES = 100;

void cli_start(struct Auth *auth)
{
	char cmd[16];

	while (true)
	{
		printf("%s> ", auth->username);
		int matches = scanf("%s", cmd);
		if (matches != 1)
		{
			printf("EOF\n");
			break;
		}

		if (strcmp(cmd, "help") == 0)
			cli_cmd_help();
		else if (strcmp(cmd, "exit") == 0)
			break;
		else if (strcmp(cmd, "list") == 0)
			cli_cmd_list(auth);
		else if (strcmp(cmd, "load") == 0)
			cli_cmd_load(auth);
		else if (strcmp(cmd, "save") == 0)
			cli_cmd_save(auth);
		else
			printf("Unknown command. Type 'help' for a list of commands.\n");
	}
}

void cli_cmd_help()
{
	printf("Available commands:\n");
	printf("	help - Show this help message\n");
	printf("	list - List your files\n");
	printf("	load - Load a file\n");
	printf("	save - Save a file\n");
	printf("	exit - Exit the CLI\n");
}

void cli_cmd_list(struct Auth *auth)
{
	char suffix[64];
	snprintf(suffix, sizeof(suffix), "_%s.txt", auth->username);

	char **files = fs_list_files(suffix, MAX_FILES);
	if (files == NULL || files[0] == NULL)
	{
		printf("No files found.\n");
		return;
	}

	printf("Your files:\n");
	for (int i = 0; i < MAX_FILES && files[i] != NULL; i++)
	{
		if (files[i][0] != '.')
			if (strstr(files[i], auth->username) != NULL)
				printf("	%s\n", files[i]);
		free(files[i]);
	}

	free(files);
}

void cli_cmd_load(struct Auth *auth)
{
	char name[64];
	char data[1024];

	printf("Filename: ");
	int len = read(0, name, 32);
	if (len <= 0)
	{
		printf("Failed to read filename.\n");
		return;
	}

	strcpy(name + len - 1, "_");
	strcpy(name + len, auth->username);
	strcpy(name + len + strlen(auth->username), ".txt");

	FILE *file = fs_load_file(name);
	if (file == NULL)
	{
		printf("File not found.\n");
		return;
	}

	fread(data, 1, sizeof(data) - 1, file);
	fclose(file);

	printf("Content:\n%s\n", data);
}

void cli_cmd_save(struct Auth *auth)
{
	char name[64];
	char data[1024];

	printf("Filename: ");
	int len = read(0, name, 32);
	if (len <= 0)
	{
		printf("Failed to read filename.\n");
		return;
	}

	strcpy(name + len - 1, "_");
	strcpy(name + len, auth->username);
	strcpy(name + len + strlen(auth->username), ".txt");

	printf("Content: ");
	len = read(0, data, 1024);
	if (len <= 0)
	{
		printf("Failed to read file content.\n");
		return;
	}

	data[len - 1] = '\0';
	if (fs_save_file(name, data) == false)
	{
		printf("Failed to save file.\n");
		return;
	}
}
