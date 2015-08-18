#!/bin/bash
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=variety.qa.pmc.com --title="QA Variety" --home=http://qa.variety.com --theme=vip/pmc-variety-2014
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=variety411.qa.pmc.com --title="QA Variety 411" --home=http://qa.variety411.com --theme=vip/pmc-411
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=variety.qa.pmc.com --title="QA Variety Latino" --home=http://qa.varietylatino.com --theme=vip/pmc-variety-latino
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=footwearnews.qa.pmc.com --title="QA Footwear News" --home=http://qa.footwearnews.com --theme=vip/pmc-footwearnews
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=wwd.qa.pmc.com --title="QA WWD" --home=http://qa.wwd.com --theme=vip/pmc-wwd-2015
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=hollywoodlife.qa.pmc.com --title="QA HollywoodLife" --home=http://qa.hollywoodlife.com --theme=vip/pmc-hollywoodlife
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=deadline.qa.pmc.com --title="QA Deadline" --home=http://qa.deadline.com --theme=vip/pmc-deadline
sudo -u www-data wp --path=/srv/www/wp pmc-site setup --domain=tvline.qa.pmc.com --title="QA TvLine" --home=http://qa.tvline.com --theme=vip/pmc-tvline-2014
