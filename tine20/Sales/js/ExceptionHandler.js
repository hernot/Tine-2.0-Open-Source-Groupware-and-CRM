/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ExceptionHandler
 * 
 * <p>Exception Handler for Sales</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.Sales.handleRequestException = function(exception, callback, callbackScope) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }
    
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    var defaults = {
        buttons: Ext.Msg.OK,
        icon: Ext.MessageBox.ERROR,
        fn: callback,
        scope: callbackScope,
        title: app.i18n._(exception.title),
        msg: app.i18n._(exception.message)
    };
    
    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);

    switch(exception.code) {
        case 910: // Sales_Exception_UnknownCurrencyCode
        case 911: // Sales_Exception_DuplicateNumber
        case 913: // Sales_Exception_InvoiceAlreadyClearedEdit
        case 914: // Sales_Exception_InvoiceAlreadyClearedDelete
        case 915: // Sales_Exception_AlterOCNumberForbidden
        case 916: // Sales_Exception_DeletePreviousInvoice
            Ext.MessageBox.show(defaults);
            break;
        // return false will the generic exceptionhandler handle the caught exception
        default:
            return false;
    }
    
    return true;
}

Tine.Tinebase.ExceptionHandlerRegistry.register('Sales', Tine.Sales.handleRequestException);