echo "Downloading dump..."
scp admin@*IP*:*PATH*/dump.tar.gz .

echo "Decompressing dump..."
tar -zxvf dump.tar.gz
echo "Deleting all tables in database..."

echo "SET FOREIGN_KEY_CHECKS = 0;" > ./temp.sql
mysqldump --add-drop-table --no-data -u *LOGIN* -p*PASSWD* *DBNAME* | grep 'DROP TABLE' >> ./temp.sql

echo "SET FOREIGN_KEY_CHECKS = 1;" >> ./temp.sql
mysql -u root -p*PASSWD* *DBNAME* < ./temp.sql
rm ./temp.sql

echo "Importing dump to mysql..."
mysql -u root -p*PASSWD* -e "use *DBNAME*; source DUMP.sql;"

echo "Removing dumps..."
rm dump.tar.gz
rm DUMP.sql



