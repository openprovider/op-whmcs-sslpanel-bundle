<?php if (!empty($view['errorMessage'])): ?>
    <?php echo $view['errorMessage']; ?>
<?php endif; ?>

<?php if (!empty($view['products'])): ?>
    <table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
        <tbody>
            <tr>
                <th><?php echo 'id'; ?></th>
                <th><?php echo 'name'; ?></th>
                <th><?php echo 'brand name'; ?></th>
                <th><?php echo 'price annually'; ?></th>
                <th><?php echo 'price biennially'; ?></th>
                <th><?php echo 'price triennially'; ?></th>
                <th><?php echo 'currency'; ?></th>
                <th><?php echo 'max period'; ?></th>
                <th><?php echo 'number of domains'; ?></th>
                <th><?php echo 'max domains'; ?></th>
            </tr>
            <?php foreach ($view['products'] as $item): ?>
                <tr>
                    <td><?php echo $item['id'] ?></td>
                    <td><?php echo $item['name'] ?></td>
                    <td><?php echo $item['brandName'] ?></td>
                    <td>
                        <?php echo $item['prices'][0]['price']['reseller']['price'] ?>
                        <br/>
                        <?php echo $item['prices'][0]['extraDomainPrice']['reseller']['price'] ?>
                        <?php if (isset($item['prices'][0]['extraDomainPrice']['reseller']['price'])) echo '(extra domain price)' ?>
                    </td>
                    <td>
                        <?php echo $item['prices'][1]['price']['reseller']['price'] ?>
                        <br/>
                        <?php echo $item['prices'][1]['extraDomainPrice']['reseller']['price'] ?>
                        <?php if (isset($item['prices'][1]['extraDomainPrice']['reseller']['price'])) echo '(extra domain price)' ?>
                    </td>
                    <td>
                        <?php echo $item['prices'][2]['price']['reseller']['price'] ?>
                        <br/>
                        <?php echo $item['prices'][2]['extraDomainPrice']['reseller']['price'] ?>
                        <?php if (isset($item['prices'][2]['extraDomainPrice']['reseller']['price'])) echo '(extra domain price)' ?>
                    </td>
                    <td><?php echo $item['prices'][0]['price']['reseller']['currency'] ?></td>
                    <td><?php echo $item['maxPeriod'] ?></td>
                    <td><?php echo $item['numberOfDomains'] ?></td>
                    <td><?php echo $item['maxDomains'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>

<a href="<?php echo $view['global']['mod_url'] ?>&action=default"><?php echo 'Back'; ?></a>
