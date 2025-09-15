######################
# Default parameters #
######################
IMAGE_NAME := kuickphp/redis

.PHONY test:
	$(eval CI_IMAGE_TAG := $(IMAGE_NAME):$(shell date +%s%N))
	CI_IMAGE_TAG=$(CI_IMAGE_TAG) docker compose up -d --force-recreate
	CI_IMAGE_TAG=$(CI_IMAGE_TAG) docker compose exec test-runner sh -c "composer up && composer fix:phpcbf && composer test:all"
	CI_IMAGE_TAG=$(CI_IMAGE_TAG) docker compose down --remove-orphans
	docker image rm $(CI_IMAGE_TAG)
