#ifndef APP_FS_H
#define APP_FS_H

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <stdbool.h>
#include <sys/stat.h>
#include <dirent.h>

FILE *fs_load_user(char *username);
void fs_save_user(char *username, char *data);

FILE *fs_load_file(char *filename);
bool fs_save_file(char *filename, char *data);

char** fs_list_files(char *suffix, int count);

#endif
