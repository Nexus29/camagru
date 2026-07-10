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
	-subj "/C=FR/ST=Paris/L=Paris/O=42/OU=Camagru/CN=localhost"
	docker compose up --build -d
	@echo "⚙️ Generating custom retro overlays inside frontend workspace..."
	@mkdir -p frontend/overlays
	@docker run --rm \
		-v $$(pwd)/frontend/overlays:/target \
		alpine:3.19 sh -c 'apk add --no-cache php82-cli php82-gd && php82 -r "\
		\$$crt = imagecreatetruecolor(640, 480); imagesavealpha(\$$crt, true); imagefill(\$$crt, 0, 0, imagecolorallocatealpha(\$$crt, 0, 0, 0, 127)); \
		\$$darkBezel = imagecolorallocate(\$$crt, 20, 20, 20); \$$screenLine = imagecolorallocate(\$$crt, 50, 50, 50); \
		imagefilledrectangle(\$$crt, 0, 0, 640, 35, \$$darkBezel); imagefilledrectangle(\$$crt, 0, 445, 640, 480, \$$darkBezel); \
		imagefilledrectangle(\$$crt, 0, 0, 35, 480, \$$darkBezel); imagefilledrectangle(\$$crt, 605, 0, 640, 480, \$$darkBezel); \
		for (\$$i = 0; \$$i < 4; \$$i++) { imagerectangle(\$$crt, 35 + \$$i, 35 + \$$i, 605 - \$$i, 445 - \$$i, \$$screenLine); } \
		imagestring(\$$crt, 3, 50, 12, \"CRT-MODE: 4:3 STANDARD\", imagecolorallocate(\$$crt, 0, 255, 0)); \
		imagepng(\$$crt, \"/target/crt-border.png\"); imagedestroy(\$$crt); \
		\
		\$$nes = imagecreatetruecolor(640, 480); imagesavealpha(\$$nes, true); imagefill(\$$nes, 0, 0, imagecolorallocatealpha(\$$nes, 0, 0, 0, 127)); \
		\$$nesRed = imagecolorallocate(\$$nes, 228, 0, 0); \$$nesGrey = imagecolorallocate(\$$nes, 107, 107, 107); \
		for (\$$i = 0; \$$i < 8; \$$i++) { imagerectangle(\$$nes, \$$i, \$$i, 640 - \$$i, 480 - \$$i, (\$$i % 2 == 0) ? \$$nesRed : \$$nesGrey); } \
		imagestring(\$$nes, 4, 35, 20, \"SELECT / START\", \$$nesGrey); \
		imagepng(\$$nes, \"/target/nes-overlay.png\"); imagedestroy(\$$nes); \
		\
		\$$dos = imagecreatetruecolor(640, 480); imagesavealpha(\$$dos, true); imagefill(\$$dos, 0, 0, imagecolorallocatealpha(\$$dos, 0, 0, 0, 127)); \
		\$$dosBlue = imagecolorallocate(\$$dos, 0, 0, 170); \$$dosWhite = imagecolorallocate(\$$dos, 255, 255, 255); \
		imagefilledrectangle(\$$dos, 0, 0, 640, 25, \$$dosBlue); imagestring(\$$dos, 4, 15, 5, \"C:\> COMMAND.COM\", \$$dosWhite); \
		for (\$$i = 0; \$$i < 5; \$$i++) { imagerectangle(\$$dos, \$$i, 25 + \$$i, 640 - \$$i, 480 - \$$i, \$$dosBlue); } \
		imagepng(\$$dos, \"/target/dos-border.png\"); imagedestroy(\$$dos); \
		echo \"✔ Overlays generated successfully inside frontend/overlays/\n\";"'

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

.PHONY: all up down restart build clean fclean re status logs shell
