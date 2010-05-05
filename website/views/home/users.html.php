<h2>all yr twitter friends</h2>

<div class="friends">
  <? if (count($users) > 0): ?>
    <? foreach ($users as $user): ?>
      <div class="friend">
        <a href="/user/<?= $user->username ?>"><?= $user->username ?></a>
        <a href="/user/<?= $user->username ?>/delete">x</a>
      </div>
    <? endforeach; ?>
  <? else: ?>
    No friends!
  <? endif; ?>
</div>
    

<a href="/users/create">add more</a>