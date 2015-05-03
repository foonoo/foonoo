<span class="type"><?= $abstract ? 'abstract ': '' ?><?php $final ? 'final ': '' ?><?= $type ?></span><h2><?= $class ?></h2>
<p><?= $details == '' ? $summary : '' ?> <?= $details->u() ?></p>

<h3>Summary</h3>
<?php if(count($constants) > 0): ?>
<h4>Constants</h4>
<table>
<?php foreach($constants as $constant): ?>
    <tr><td><?= t('type_link', $constant['type']) ?></td><td><a href="#<?= $constant['link'] ?>"><?= $constant['name'] ?></a></td><td><?= $constant['summary'] ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($properties) > 0): ?>
<h4>Properties</h4>
<table>
<?php foreach($properties as $property): ?>
    <tr><td><?= $property['visibility'] ?> <?= $property['static'] ? 'static' : '' ?> <?= t('type_link', $property['type']) ?></td><td><a href="#<?= $property['link'] ?>"><?= $property['name'] ?></a></td><td><?= $property['summary'] ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($methods) > 0): ?>
<h4>Methods</h4>
<table id="methods-summary-table">
<?php $methodPrototypes = array(); 
    foreach($methods as $i => $method): ?>
    <?php 
    $typedParams = array();
    foreach($method['parameters'] as $parameter)
    {
        $typedParams[] = t('type_link', $parameter['type']) . ($parameter['byreference'] ? '&' : '') ." {$parameter['name']}";
    }
    $methodPrototypes[$i] = implode(", ", $typedParams);
    ?>
    <tr><td><?= $method['final'] ? 'final ' : '' ?><?= $method['visibility'] ?> <?= t('type_link', $method['return']['type']) ?></td><td><a href="#<?= $method['link'] ?>"><?= $method['name'] ?></a> (<?= $methodPrototypes[$i]?>)<p><?= $method['summary'] ?></p></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($constants) > 0): ?>
<h3>Constants</h3>
<?php foreach($constants as $constant): ?>
<div class="prototype">
    <a name="<?= $constant['link'] ?>" class="prototype-anchor"></a>
    <?= t('type_link', $constant['type']) ?> <span class="item-name"><?= $constant['name'] ?></span> = <?= $constant['value'] ?>
</div>
<div class="prototype-description">
<p><?= "{$constant['summary']} {$constant['details']}" ?></p>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if(count($properties) > 0): ?>
<h3>Properties</h3>
<?php foreach($properties as $property): ?>
<div class="prototype">
    <a name="<?= $property['link'] ?>" class="prototype-anchor"></a>
    <?= $property['visibility'] ?> <?= $property['static'] ? 'static' : '' ?> <?= t('type_link', $property['type']) ?> <span class="item-name"><?= $property['name'] ?></span> <?= ($property['default'] != '' ? " = <code>{$property['default']}</code>" : '' ) ?>
</div>
<div class="prototype-description">
    <p><?= "{$property['summary']} {$property['details']}" ?></p>
</div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if(count($methods) > 0): ?>
<h3>Methods</h3>
<?php foreach($methods as $i => $method): ?>
<div class="prototype">
    <a name="<?= $method['link'] ?>" class="prototype-anchor"></a>
    <?= $method['final'] ? 'final ' : '' ?><?= $method['visibility'] ?> <?= $method['static'] ? 'static ' : '' ?><?= $method['abstract'] ? 'abstract ' : '' ?><?=t('type_link', $method['return']['type']) ?> <span class="item-name"><?= $method['name'] ?></span> (<?= $methodPrototypes[$i]?>)
</div>
<div class="prototype-description">
    <p><?= "{$method['summary']} {$method['details']->u()}" ?></p>
    <?php if(count($method['parameters'])): ?>
        <div class="subheader">Parameters</div>
        <table class="subheader-table">
        <?php foreach($method['parameters'] as $parameter): ?>
            <tr>
                <td><?= t('type_link', $parameter['type']->u()) ?> <?= $parameter['name'] ?></td>
                <td><?= $parameter['description'] ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if($method['return']['type']['type'] != ''): ?>
        <div class="subheader">Return</div>
        <table class="subheader-table">
            <tr><td><?= t('type_link', $method['return']['type']) ?></td><td><?= $method['return']['description'] ?></td></tr>
        </table>
    <?php endif; ?>
    <?php if(count($method['throws'])): ?>
        <div class="subheader">Throws</div>
        <table class="subheader-table">
            <?php foreach($method['throws'] as $throw): ?>
            <tr><td><?= t('type_link', $throw['type']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>  
    <?php if(count($method['sees'])): ?>
        <div class="subheader">See Also</div>
        <table class="subheader-table">
            <?php foreach($method['sees'] as $see): ?>
            <tr><td><?= t('type_link', $see['type']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>        
</div>
<?php endforeach; ?>
<?php endif; ?>