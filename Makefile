NAME        = camagru

all: up

up:
	@echo "Launching $(NAME) environment..."
	docker-compose up -d

build:
	@echo "Building $(NAME) container configurations..."
	docker-compose up --build -d

down:
	@echo "Stopping $(NAME) containers..."
	docker-compose down

restart:
	@echo "Restarting $(NAME) cluster..."
	docker-compose restart

clean: down

fclean:
	@echo "Deep cleaning $(NAME) environment (wiping data volumes & images)..."
	docker-compose down --volumes --rmi all

logs:
	docker-compose logs -f

re: fclean build

.PHONY: all up down restart build clean fclean re status logs shell
