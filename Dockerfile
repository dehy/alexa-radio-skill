FROM ubuntu:18.04

ARG BUILD_DATE
ARG VCS_REF
ARG VCS_BRANCH
LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="MOROW for Alexa" \
      org.label-schema.url="https://www.morow.com/" \
      org.label-schema.vcs-ref=$VCS_REF \
      org.label-schema.vcs-url="https://github.com/akerbis/morow-alexa" \
      org.label-schema.vendor="AkerBis" \
      org.label-schema.schema-version="1.0"

ENV APP_ENV "prod"
ENV AMAZON_APP_ID ""
ENV CONFIG_FILEPATH ""

WORKDIR /var/www

#HEALTHCHECK --interval=30s --timeout=30s --start-period=45s --retries=3 CMD [ "/usr/local/bin/healthcheck.sh" ]

COPY docker/setup /setup
RUN bash /setup/setup.sh

COPY --chown=alexa:alexa . /var/www
RUN bash /setup/configure.sh && rm -rf /setup

COPY docker/config /etc

EXPOSE 80
ENTRYPOINT ["bash", "/entrypoint.sh"]
