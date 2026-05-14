all:
		docker compose -f ./docker-compose.yml up -d --build

clean:
		docker compose -f ./docker-compose.yml down

fclean:
		docker compose -f ./docker-compose.yml down -v

re:
		make fclean
		make all

up: 
		docker compose -f ./docker-compose.yml up -d --build

down: 
		docker compose -f ./docker-compose.yml down

populate:
		docker exec php_apache bash -c "mkdir -p /var/www/html/uploads/1 && chmod 777 /var/www/html/uploads/1 && cp -R /usr/local/bin/script/images/* /var/www/html/uploads/1/ && php /usr/local/bin/script/populate.php"
# 		users:
# 			user1@yopmail.fr/user1:user1
# 			user2@yopmail.fr/user2:user2

prune:
		make clean
		docker system prune

reboot:
		make clean
		make all

.PHONY: all clean fclean re up down populate prune reboot
