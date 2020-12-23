<?php
$schemeAndHost = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"];
$formUrl = $schemeAndHost . $_SERVER["SCRIPT_NAME"] .$view['global']['mod_url'];
?>

<?php if (!empty($isPost)): ?>
    Product success added
<?php else: ?>
    <form method="post" action="<?php echo $formUrl ?>&action=import-ssl-products">
        <label>
            <span>Product group ID</span>
            <input type="number" name="product-group-id" required>
        </label>
        <br><br>
        <button type="submit">Submit</button>
    </form>
<?php endif; ?>

<br>
<a href="<?php echo $view['global']['mod_url'] ?>&action=default">
    <?php echo 'Back'; ?>
</a>
