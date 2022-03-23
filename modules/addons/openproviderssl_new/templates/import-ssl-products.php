<?php
$schemeAndHost = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"];
$formUrl = $schemeAndHost . $_SERVER["SCRIPT_NAME"] .$view['global']['mod_url'];
$noError = empty($view['errorMessage']);
?>

<?php if (!$noError): ?>
    <?php echo $view['errorMessage']; ?>
    <br>
<?php endif; ?>

<?php if (!empty($isPost) && $noError): ?>
    Products success added
<?php elseif ($noError): ?>
    <style>
        .table-row {
            display: grid;
            grid-auto-flow: column;
            grid-template-columns: 150px 150px;
        }
        .form-block {
            padding: 20px;
            border: 2px solid #00000030;
            display: inline-block;
            border-radius: 10px;
        }
    </style>
    <br>
    <form method="post" action="<?php echo $formUrl ?>&action=import-ssl-products">
        <div class="form-block">
            <label class="table-row">
                <span>Product group ID</span>
                <input type="number" name="product-group-id" required>
            </label>
            <label class="table-row">
                <span>% of margin</span>
                <input type="number" name="margin-percent" required>
            </label>
            <br>
            <button type="submit">Import SSL products</button>
        </div>
    </form>
<?php endif; ?>

<br><br>
<a href="<?php echo $view['global']['mod_url'] ?>&action=default">
    <?php echo 'Back'; ?>
</a>
