#include "fs.h"

const char *FS_USERS_ROOT = "./users";
const char *FS_FILES_ROOT = "./files";

FILE *fs_load_user(char *username)
{
	char path[128];
	snprintf(path, sizeof(path), "%s/.%s_login", FS_USERS_ROOT, username);
	return fopen(path, "r");
}

void fs_save_user(char *username, char *data)
{
	char path[128];
	snprintf(path, sizeof(path), "%s/.%s_login", FS_USERS_ROOT, username);

	FILE *file = fopen(path, "w");
	fprintf(file, "%s", data);
	fclose(file);
}

FILE *fs_load_file(char *filename)
{
	char path[128];
	snprintf(path, sizeof(path), "%s/%s", FS_FILES_ROOT, filename);

	return fopen(path, "r");
}

bool fs_save_file(char *filename, char *data)
{
	char path[128];
	snprintf(path, sizeof(path), "%s/%s", FS_FILES_ROOT, filename);

	FILE *file = fopen(path, "w");
	fprintf(file, "%s", data);
	fclose(file);
	return true;
}

char** fs_list_files(char *suffix, int count)
{
	char path[128];
	snprintf(path, sizeof(path), "%s", FS_FILES_ROOT);

	struct stat st;
	if (stat(path, &st) != 0 || !S_ISDIR(st.st_mode))
		return NULL;

	int index = 0;
	char **files = malloc(sizeof(char*) * count);
	for (int i = 0; i < count; i++)
		files[i] = NULL;

	DIR *dir = opendir(path);
	struct dirent *entry;
	while ((entry = readdir(dir)) != NULL && index < count)
	{
		if (entry->d_type == DT_REG)
		{
			if (strstr(entry->d_name, suffix) == NULL)
				continue;
			files[index] = strdup(entry->d_name);
			index++;
		}
	}

	closedir(dir);
	return files;
}
