<? if ($username) { ?>
  Hello, <?= $username ?>.
<? } else { ?>
  <a href="#" onclick="window.location.href = prompt('enter your name');">click me</a>
<? } ?>