<span class="type"><?= $abstract ? 'abstract ': '' ?><?php $final ? 'final ': '' ?><?= $type ?></span><h2><?= $class ?></h2>
<p><?= $details == '' ? $summary : '' ?> <?= $details->u() ?></p>

<h3>Summary</h3>
<?= t('summary', array('split' => true, 'items' => $constants, 'item_type' => 'Constants')) ?>
<?= t('summary', array('split' => true, 'items' => $properties, 'item_type' => 'Properties')) ?>
<?= t('method_summary', array('items' => $methods, 'item_type' => 'Methods', 'postfix' => 'method_arguments')) ?>

<?= t('details', array('items' => $constants, 'item_type' => 'Constants')) ?>
<?= t('details', array('items' => $properties, 'item_type' => 'Properties')) ?>
<?= t('details', array('items' => $methods, 'item_type' => 'Methods', 'postfix' => 'method_arguments')) ?>
