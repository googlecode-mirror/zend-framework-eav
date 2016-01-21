# Reference #

## Quick start ##

### Prepare tables ###
For example if you have "products" table and you want to save EAV attributes for this table. Then create several tables where EAV attributes will be stored.
Example:
  * products\_int,
  * products\_decimal
  * products\_string
  * products\_text

Each table should contain next fields:
  * id(INT) – primary key(auto increment)
  * entity\_id(INT) – identifier of your record in the products table
  * attribute\_id(INT) – identifier of attribute
  * value – in this field the value of attribute will be stored. Type of this field should be different for each table(int, decimal, varchar, text)

Also you should have table of attributes with next fields inside:
  * id, primary key
  * type, type of attribute
  * name, name of attribute

The names of these fields do not necessarily have to be called as above. Here we have the names of fields by default. If you don't have such table then create it.

Then create several records in the 'attributes' table

**attributes**
| **attribute\_id** | **attribute\_type** | **attribute\_name** |
|:------------------|:--------------------|:--------------------|
| 1                 | int                 | quantity            |
| 2                 | string              | title               |
| 3                 | text                | description         |


### Extends Eav ###
You can specify the others names of required fields:
```

class Eav_Product extends Eav
{
    protected $_entityFieldId = 'id'; // name of primary key of products table
    protected $_attributeTableName = 'attributes'; // name of 'attributes' table
    protected $_attributeFieldId   = 'attribute_id'; // name of primary key of attributes table
    protected $_attributeFieldType = 'attribute_type'; // field where attribute type is stored
    protected $_attributeFieldName = 'attribute_name'; // field where attribute name is stored
}

```

### Save attributes ###
```

$productModel = new Zend_Db_Table('products');
$productEav = new Eav_Product($productModel);

$products = $productModel->find(array(1,2,3));

foreach ($products as $product) {
    $productEav->setAttributeValue($product, 'quantity', 10);
    $productEav->setAttributeValue($product, 'title', 'product title');
    $productEav->setAttributeValue($product, 'description', 'my description');
}

```

### Get attributes ###
```
$productModel = new Zend_Db_Table('products');
$productEav = new Eav_Product($productModel);

$product = $productModel->find(1)->current();
echo $productEav->getAttributeValue($product, 'quantity');
echo $productEav->getAttributeValue($product, 'title');
echo $productEav->getAttributeValue($product, 'description');

// or using attribute id
echo $productEav->getAttributeValue($product, '1');

// or using attribute object(Row)
$attributeTable = new Zend_Db_Table('attributes');
$attribute = $attributeTable->find(2)->current();
echo $productEav->getAttributeValue($product, $attribute);

// or using eav object
$attribute = $productEav->getAttribute('title');
echo $productEav->getAttributeValue($product, $attribute);
```

## Full example ##
Controller:
```

$attributeModel = new Zend_Db_Table('attributes');
$productModel = new Zend_Db_Table('products');
$productEav = new Eav_Product($productModel);

$products = $productModel->find(array(1,2,3));
$attributes = $attributeModel->fetchAll();

```

View:
```
<?php foreach ($this->products as $product): ?>

    <b><?php echo $product->title; ?></b><br />
    <?php foreach ($this->attributes as $attribute): ?>

        <?php echo $attribute->label; ?>:
        <?php echo $this->eav->getAttributeValue($product, $attribute); ?><br />

    <?php endforeach; ?>

<?php endforeach; ?>
```

## Speed up ##
With the approach above, every time you want to get the value of attribute you will have query to the database. You can get the attribute values using only one query to the database. To do so RowClass of your model should implement Eav\_Row\_Interface. If you are not using other RowClass then you can simply use a ready-made class Eav\_Row. Below are some examples:

```

$attributeModel = new Zend_Db_Table('attributes');

$productModel = new Zend_Db_Table('products');
$productModel->setRowClass('Eav_Row');

$productEav = new Eav_Product($productModel);

$products = $productModel->find(array(1,2,3));

// Loading all attributes
$attributes = $attributeModel->fetchAll();
$productEav->loadAttributes($rows, $attributes);

foreach ($products as $product) {
    // no more queries
    echo $productEav->getAttributeValue($product, 'title');
    echo $productEav->getAttributeValue($product, 'description');
    echo $productEav->getAttributeValue($product, 'quantity');
}

// Loading some attributes
$where = $attributeModel->select()->where("name in(?)", array('title', 'description'));
$attributes = $attributeModel->fetchAll($where);
// or
$attributes = $attributeModel->find(array(2,3));

$productEav->loadAttributes($products, $attributes);

foreach ($products as $product) {
    // no more queries
    echo $productEav->getAttributeValue($product, 'title');
    echo $productEav->getAttributeValue($product, 'description');

    // here we have one query to database
    echo $productEav->getAttributeValue($product, 'quantity');
}

```