<?php
$method = $templateVariables;
$typedParams = array();
foreach($method['parameters'] as $parameter)
{
    $typedParams[] = trim(t('type_link', $parameter['type']) . ' ' . ($parameter['byreference'] ? '&' : '') ."{$parameter['name']}");
}
echo '(' . implode(",&nbsp;", $typedParams) . ')';