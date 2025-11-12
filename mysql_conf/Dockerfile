FROM mysql:8.0

ARG CONFIG=node1.cnf

COPY ${CONFIG} /etc/mysql/conf.d/gtid.cnf
RUN chown root:root /etc/mysql/conf.d/gtid.cnf \
    && chmod 0644 /etc/mysql/conf.d/gtid.cnf
