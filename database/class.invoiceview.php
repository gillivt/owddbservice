<?php
// If it's going to need the database, then it's 
// probably smart to require it before we start.
require_once(LIB_PATH . DS . 'class.database_object.php');

class InvoiceView extends DatabaseObject {

    protected static $table_name = "invoice_view";
    protected static $db_fields = array('id', 'invoicedate', 'invoiceamount', 'invoicevatamount', 
        'invoicepaymenttype', 'invoicechequenumber', 'clientid', 'productid', 'traderid', 'clientfirstname',
        'clientlastname', 'clienthousenumber', 'clientaddress1', 'clientaddress2', 'clienttown',
        'clientcounty', 'clientpostcode', 'clientlandline', 'clientmobile', 'clientemail', 'productkey',
        'productdescription', 'productprice', 'productmisc', 'traderfirstname', 'traderlastname',
        'traderaddress1', 'traderaddress2', 'tradertown', 'tradercounty', 'traderpostcode', 'tradertelephone',
        'tradertradingname', 'tradervatnumber', 'traderemail', 'traderbankaccountnumber', 'traderbanksortcode',
        'traderbankname', 'traderbankaccountname', 'traderstripereference', 'traderlogourl', 'traderlastinvoicenumber');
        
    public $id;
    public $invoicedate;
    public $invoiceamount;
    public $invoicevatamount;
    public $invoicepaymenttype;
    public $invoicechequenumber;
    public $clientid;
    public $productid;
    public $traderid;
    public $clientfirstname;
    public $clientlastname;
    public $clienthousenumber;
    public $clientaddress1;
    public $clientaddress2;
    public $clienttown;
    public $clientcounty;
    public $clientpostcode;
    public $clientlandline;
    public $clientmobile;
    public $clientemail;
    public $productkey;
    public $productdescription;
    public $productprice;
    public $productmisc;
    public $traderfirstname;
    public $traderlastname;
    public $traderaddress1;
    public $traderaddress2;
    public $tradertown;
    public $tradercounty;
    public $traderpostcode;
    public $tradertelephone;
    public $tradertradingname;
    public $tradervatnumber;
    public $traderemail;
    public $traderbankaccountnumber;
    public $traderbanksortcode;
    public $traderbankname;
    public $traderbankaccountname;
    public $traderstripereference; 
    public $traderlogourl;
    public $traderlastinvoicenumber;
}
?>