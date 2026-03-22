CXX = clang
TARGET = main
SRCDIR = src
OBJDIR = obj
BINDIR = bin
SOURCES = $(wildcard $(SRCDIR)/*.c)
OBJECTS = $(patsubst $(SRCDIR)/%.c, $(OBJDIR)/%.o, $(SOURCES))
CFLAGS = -std=gnu99 -Iinc
LDFLAGS = 

$(BINDIR)/$(TARGET): $(OBJECTS)
	@mkdir -p $(BINDIR)
	$(CXX) $(CFLAGS) -o $@ $^ $(LDFLAGS)

$(OBJDIR)/%.o: $(SRCDIR)/%.c
	@mkdir -p $(OBJDIR)
	$(CXX) $(CFLAGS) -c $< -o $@

all: $(BINDIR)/$(TARGET)

run: all
	./$(BINDIR)/$(TARGET)

clean:
	rm -rf $(OBJDIR) $(BINDIR)

deploy: all
	docker compose up -d --build
