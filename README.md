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

### Alterações realizadas diretamente nas classes bases que podem estar difernete ao atualizar versão da TCPDF

Classe IXCTCPDF método cell(): o parâmetro $stretch teve seu padrão alterado. Antes ele vinha com zero, alteramos ele para o valor 1.
Classe IXCTCPDF método getFontsList(): Alterado logica do método. Ele utilizava opendir, readdir e closedir para ler o diretório de fontes e carrega-las em um array. Lógica alterada para ja retornar pronto o array que antes era montado.
Classe IXCTCPDF método _destroy(): Alterado logica do método. Ele utilizava opendir, readdir e closedir para ler o diretório da /tmp/ e deletar os PDFs ali criado. Lógica alterada para não utilizar mais readdir e sim utilizar glob para a busca de arquivos ficar mais leve.