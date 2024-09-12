FROM nginx:alpine

# Remove default configuration file
RUN rm /etc/nginx/conf.d/default.conf

# Copy custom configuration file from the current directory
COPY ./resources/dc-ops/nginx/default.conf /etc/nginx/conf.d

# Expose port 80
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]