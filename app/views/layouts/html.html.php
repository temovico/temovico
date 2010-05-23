<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?=$this->title;?></title>

  <? foreach ($this->stylesheets as $stylesheet): ?>
    <? if (!preg_match("/^http/", $stylesheet)) { $stylesheet = '/css/' . $stylesheet; } ?>
    <link href="<?= $stylesheet ?>" rel="stylesheet" type="text/css" />
  <? endforeach; ?>

  <? foreach ($this->javascripts as $javascript): ?>
    <? if (!preg_match("/^http/", $javascript)) { $javascript = '/js/' . $javascript; } ?>
    <script src="<?= $javascript ?>" type="text/javascript"></script>
  <? endforeach; ?>
  
  <? if ($this->extra_headers): ?>
    <? foreach ($this->extra_headers as $extra_header): ?>
      <?= $extra_header ?>
    <? endforeach; ?>
  <? endif; ?>
</head>
<body>
  <div class="c-<?= $controller_name ?> a-<?= $action ?>">
    <h1><?= $GLOBALS['temovico']['website_name'] ?></h1>
    <div id="content">
        <? $this->yield(); ?>
    </div>
  </div>
</body>
</html>