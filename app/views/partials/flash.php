<?php $successMessage = Session::flash('success'); ?>
<?php $errorMessage = Session::flash('error'); ?>

<?php if ($successMessage !== null): ?>
    <div class="flash flash-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="flash flash-error"><?= e($errorMessage) ?></div>
<?php endif; ?>
