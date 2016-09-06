<?php if (!empty($view['errorMessage'])): ?>
    <?php echo $view['errorMessage']; ?>
<?php else: ?>
    <?php echo 'success'; ?>
<?php endif; ?>

<a href="<?php echo $view['global']['mod_url'] ?>&action=default"><?php echo 'Back'; ?></a>
