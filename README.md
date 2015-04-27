Smallable Morin Bundle
========================

Install
=======

On `composer.json` :

```
"require": {
        ...
        "chekib/smallable-morin-bundle": "dev-master"
    },
```

```
    "repositories": [
        ...
        {"type": "vcs", "url": "https://github.com/chekib/smallable-morin-bundle.git"}
    ],
```

On `appKernel.php`

```
  new Smallable\Logistics\MorinBundle\MorinBundle()
```
  
Run : ```../composer.phar update```
  
Command Examples
====================

In your own bundle, you can now write your command and service, using and overriding the morin bundle services.

File `MorinExportCommand.php`

```
class MorinExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smallable:morin:export')
            ->setDescription('Morin Export Command')
            ->addArgument('interface', InputArgument::REQUIRED, 'Morin File (37B, 79B, ....)')
            ->setHelp('
            <info>Morin Command</info>

            Interfaces :
            - 37B : Generate orders file and send it to Morin

            Exemple : php app/console smallable:morin:export 37B

            ');
    }
    
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $interface = $oInput->getArgument('interface');
        if (in_array($interface, array('37B'))){
            $MorinService = $this->getContainer()->get('morin.'.$interface);
            $MorinService->process();
        }
    }
}
```

File `Morin37B.php`

```
namespace Smallable\OrderMorinBundle\Service;

use Smallable\Logistics\MorinBundle\Service\MorinExportService;
use Smallable\Logistics\MorinBundle\Service\MorinExportInterface;
use Smallable\OrderBundle\Entity\LogHistoryLine;
use Smallable\OrderBundle\Entity\LogHistoryLineCategory;

class Morin37B extends MorinExportService implements MorinExportInterface
{

    public function init()
    {
        $this->fileName = '37B';
        parent::init();
    }

    public function fetchData()
    {

        $orders = $this->oContainer->get('doctrine')->getManager()
            ->getRepository('SmallableOrderBundle:CustomerOrder')->getOrdersForDispatch();

        $itemManager = $this->oContainer->get('smallable.order.customer_item_manager');
        $referenceManager = $this->oContainer->get('smallable.order.reference_manager');

        $ordersToDispatch = array();
        foreach($orders as $orderInfos) {
        
            try {
                $order = $this->oContainer->get('doctrine')->getManager()
                         ->getRepository('SmallableOrderBundle:CustomerOrder')->findOneById($orderInfos['id']);

                if (! $itemManager->isForbiddenToSentToWarehouse($order)) {
                    $linesToDispatch = $itemManager->getLinesToDispatch($order);

                    if (count($linesToDispatch)) {
                        $ordersToDispatch[$order->getId()]['order'] = $order;
                        $ordersToDispatch[$order->getId()]['lines'] = $linesToDispatch;
                    }
                }
            } catch(\Exception $error) {
                //$this->info('LOGISTIC-ORDER : ORDER #'.$orderInfos['id']." NOT FOUND");
            }
        }

        // Generate the picking for each order
        $lineStatus = $referenceManager->get('Status', \Smallable\OrderBundle\Entity\BasketLine::PICKING_LINE_SENT_TO_WAREHOUSE);
        foreach ($ordersToDispatch as $id => & $aOrder) {
            $aOrder['picking'] = $itemManager->duplicateFromReturnOrPicking('Picking', false, $aOrder['order'], $lineStatus, $aOrder['lines']);

        }

        return $ordersToDispatch;
    }

    public function transformData($source)
    {
        $aData = array();
        $totals = new \stdClass();
        $totals->numberOfOrders = 0;
        $totals->numberOfLines = 0;
        foreach ($source as $id => $aOrder) {

            $totals->numberOfOrders++;

            // Header
            foreach ($this->fileMap->getLines() as  $line) {

                if ($line->getType() == 'header') {
                    $this->transformDataLine($line, $aOrder['order'], null, $aData);
                }
            }

            // Products
            foreach($aOrder['lines'] as $aBasketLine) {

                $totals->numberOfLines++;
                foreach ($this->fileMap->getLines() as  $line) {

                    if ($line->getType() == 'product') {
                        $this->transformDataLine($line, $aBasketLine['line'], $aOrder['order'] , $aData);
                    }
                }
            }

            // Options / Details
            foreach ($this->fileMap->getLines() as  $line) {

                if ($line->getType() == 'comment') {
                    $this->transformDataLine($line, $aOrder['order'], null, $aData);
                }
            }

            // Footer
            foreach ($this->fileMap->getLines() as  $line) {

                if ($line->getType() == 'footer') {
                    $this->transformDataLine($line, $aOrder['order'], null, $aData);
                }
            }
        }

            // File Footer

            foreach ($this->fileMap->getLines() as  $line) {

                if ($line->getType() == 'endoffile') {
                    $this->transformDataLine($line, $totals, null, $aData);
                }
            }

        return $aData;
    }

    public function transformDataLine($line, $mainObject, $order, &$aData) {

        foreach ($line->getFields() as $oField) {
            if ($oField->getSourceType() == 'order') {
                $object = $order;
            } else {
                $object = $mainObject;
            }
            $aField = $this->transformField($object, $oField);
            $aField = array_merge($aField, array('line' => (method_exists($mainObject, 'getId')?$mainObject->getId(): 1)));
            $aData[] = $aField;
        }
    }

    public function writeData($aData)
    {
        return parent::writeData($aData);
    }

    public function terminate($aData, $aFile)
    {

        $referenceManager = $this->oContainer->get('smallable.order.reference_manager');
        $itemManager = $this->oContainer->get('smallable.order.customer_item_manager');

        foreach ($aData as $aOrder) {
            $aOrder['order']->setCurrentStatus($referenceManager->get('Status', \Smallable\OrderBundle\Entity\CustomerOrder::IN_PREPARATION));
            $aOrder['picking']->setCurrentStatus($referenceManager->get('Status', \Smallable\OrderBundle\Entity\CustomerPICKING::SENT_TO_WAREHOUSE));

            $itemManager->log($aOrder['order'], LogHistoryLine::TYPE_LOGISTIC, "File sent : ".$aFile['fileName'], LogHistoryLineCategory::AUTOMATIC);

        }

        $this->oContainer->get('doctrine')->getManager()->flush();
        $this->oContainer->get('doctrine')->getManager()->clear();
    }
}
```

Xml pattern file
=================

Each service use xml pattern file to describe the morin files. Morin files are either raw text or xml.
They are located in `ressources/xml`.
This pattern matchs the following scheme :

```
<file>
        <type></type>
        <interfaceName></interfaceName>
        <length></length>
        <line> (multiple)
                <type></type>
                <field> (multiple)
                    <name></name>
                    <length></length>
                    <position></position>
                    <info></info>                
                </field> 
        </line>
</file>
```

About `<file>` : `<type>` can be raw or xml

About `<line>` : optional `<type>` allow to describe/generate a file with header, footer ...

About field : `<name>`, `<length>`, `<position>` and `<info>`  should match the morin documentation.
`<prefix>` : add a prefix to the value
`<source>` : indicate the the getters to use to get data from object sent to `transformField` method. Multiple <ource> is possible.
`<defaultValue>` : used to fill with a default value instead of getting by `source`
`<type>` : possible values :  `sku` or `type`

```
<?xml version="1.0" encoding="UTF-8" ?>
<file>
   <type>raw</type>
   <interfaceName>37B</interfaceName>
   <length>255</length>

    <line>
        <type>header</type>
        <field name="D1">
            <name>Type Enregistrement</name>
            <length>1</length>
            <position>1</position>
            <info>D</info>
            <defaultValue>D</defaultValue>
        </field>
        <field name="D2">
            <name>No Commande</name>
            <length>10</length>
            <position>2</position>
            <info>SExxxxxx</info>
            <prefix>SE</prefix>
            <source>id</source>
        </field>
        
 ...
 ```
 
 ```
        <field name="D3">
            <name>Nom Destinataire</name>
            <length>30</length>
            <position>12</position>
            <info>NOM Prénom</info>
            <source>deliveryAddress</source>
            <source>firstnameLastname</source>
        </field>
 ```        
        





