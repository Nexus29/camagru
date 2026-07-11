NAME        = camagru

all: up

up:
	@echo "Launching $(NAME) environment..."
	docker compose up -d

build:
	@echo "Building $(NAME) container configurations..."
	mkdir -p docker/nginx/ssl
	openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
	-keyout docker/nginx/ssl/camagru.key \
	-out docker/nginx/ssl/camagru.crt \
	-subj "/C=IT/ST=Florence/L=Florence/O=42/OU=Camagru/CN=localhost"
	docker compose up --build -d
	@./setup.sh

down:
	@echo "Stopping $(NAME) containers..."
	docker compose down

restart:
	@echo "Restarting $(NAME) cluster..."
	docker compose restart

clean: down

fclean:
	@echo "Deep cleaning $(NAME) environment (wiping data volumes & images)..."
	docker compose down --volumes --rmi all
	@if [ -d "docker/nginx/ssl" ]; then \
		echo "Removing SSL certificates via docker agent..."; \
		docker run --rm -v $$(pwd):/workspace alpine rm -rf /workspace/docker/nginx/ssl; \
	fi
	@echo "🧼 Cleaning up code-generated frontend overlays..."
	rm -rf frontend/overlays

logs:
	docker compose logs -f

re: fclean build

.PHONY: all up down restart build clean fclean re logs
