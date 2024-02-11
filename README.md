# database-web-ui
Basic web UI for PostgreSQL databases with dropdown menus with foreign key constraint.

It gets data from `information_schema`, therefore it will display any database automatically.

It is pretty **bad written** and ***unsafe***, because I didn't care enough to check session creation time to expire it. To avoid data loss, it logs every request.

But it has dropdown menus! :D
