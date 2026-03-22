#ifndef APP_CLI_H
#define APP_CLI_H

#include "auth.h"

#include <unistd.h>

void cli_start(struct Auth *auth);

void cli_cmd_help();
void cli_cmd_list(struct Auth *auth);
void cli_cmd_load(struct Auth *auth);
void cli_cmd_save(struct Auth *auth);

#endif
