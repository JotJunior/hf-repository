<?php

namespace Jot\HfRepository\Swagger;

use Hyperf\Swagger\HttpServer;

class SwaggerHttpServer extends HttpServer
{
    protected function getHtml(): string
    {
        if (! empty($this->config['html'])) {
            return $this->config['html'];
        }

        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="SwaggerUI" />
  <title>SwaggerUI</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" crossorigin></script>
<script>
  window.onload = () => {
    window.ui = SwaggerUIBundle({
      url: GetQueryString("search"),
      dom_id: '#swagger-ui',
    });
  };
  function GetQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg); //获取url中"?"符后的字符串并正则匹配
        var context = "";
        if (r != null)
            context = decodeURIComponent(r[2]);
        reg = null;
        r = null;
        return context == null || context == "" || context == "undefined" ? "/http.json" : context;
    }
</script>
</body>
</html>
HTML;
    }
}