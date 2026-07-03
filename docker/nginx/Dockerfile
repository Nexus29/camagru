FROM nginx:alpine

COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

COPY ./frontend /var/www/html/frontend

EXPOSE 80 443
CMD ["nginx", "-g", "daemon off;"]
