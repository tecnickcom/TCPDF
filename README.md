# [TCPDF](https://github.com/tecnickcom/TCPDF) by IXCSoft

Pacote adaptado para funcionamento no ERP IXCProvedor.

### Instalação

`composer require ixc-soft/tcpdf`

## Code Quality

Para verificar compatibilidade de código com o PHP >= 7.0

`./vendor/bin/phpcs -p . --standard=vendor/phpcompatibility/php-compatibility/PHPCompatibility --runtime-set testVersion 7.0 ./ --extensions=php --ignore=*/vendor/*`

Para analisar a qualidade código com Code Sniffer

`php ./vendor/bin/phpcs --extensions=php --standard=rules-cs.xml ./`

### Documentação completa do pacote

https://github.com/tecnickcom/TCPDF