/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        refactor this (requiredGrant is required even if skipGrants == true) 
 */
 
 Ext.namespace('Tine', 'Tine.widgets');
 
 Tine.widgets.ActionUpdater = function(config) {
    var actions = config.actions || [];
    delete(config.actions);
    
    Ext.apply(this, config);
    this.addActions(actions);
 };
 
 Tine.widgets.ActionUpdater.prototype = {
    /**
     * @cfg {Array|Toolbar} actions
     * all actions to update
     */
    actions: [],
    
    /**
     * @cfg {String} grantsProperty
     * property in the record to find the grants in
     */
    grantsProperty: 'account_grants',
    
    /**
     * @cfg {String} containerProperty
     * container property of records (if set, grants are expected to be  a property of the container)
     */
    containerProperty: 'container_id',
    
    /**
     * add actions to update
     * @param {Array|Toolbar} actions
     */
    addActions: function(actions) {
        switch (typeof(actions)) {
            case 'object':
                if (typeof(actions.each) == 'function') {
                    actions.each(this.addAction, this);
                } else {
                    for (var action in actions) {
                        this.addAction(actions[action]);
                    }
                }
            break;
            case 'array':
                for (var i=0; i<actions.length; i++) {
                    this.addAction(actions[i]);
                }
            break;
        }
    },
    
    /**
     * add a single action to update
     * @param {Ext.Action} action
     */
    addAction: function(action) {
        // if action has to initialConfig it's no Ext.Action!
        if (action.initialConfig) {
            
            // in some coses our actionUpdater config is not in the initial config
            // this happens for direct extensions of button class, like the notes button
            if (action.requiredGrant) {
                Ext.applyIf(action.initialConfig, {
                    requiredGrant: action.requiredGrant,
                    actionUpdater: action.actionUpdater,
                    allowMultiple: action.allowMultiple,
                    singularText: action.singularText,
                    pluralText: action.pluralText,
                    translationObject: action.translationObject
                });
            }
            
            this.actions.push(action);
        }
    },
    
    /**
     * performs the actual update
     * @param {Array|SelectionModel} records
     */
    updateActions: function(records) {
        
        if (typeof(records.getSelections) == 'function') {
            records = records.getSelections();
        } else if (typeof(records.beginEdit) == 'function') {
            records = [records];
        }
        
        var grants = this.getGrantsSum(records);
        
        this.each(function(action) {
            // action updater opt-in fn has precedence over generic action updater!
            if (typeof(action.actionUpdater) == 'function') {
                action.actionUpdater.call(action.scope||action, grants, records);
            } else {
                this.defaultUpdater(action, grants, records);
            }
        }, this);
        
    },
    
    /**
     * default action updater
     * 
     * - sets disabled status based on grants and required grant
     * - sets disabled status based on allowMutliple
     * - sets action text (signular/plural text)
     * 
     * @param {Ext.Action} action
     * @param {Object} grants
     * @param {Object} grants
     */
    defaultUpdater: function(action, grants, records) {
        var requiredGrant = action.requiredGrant ? action.requiredGrant : action.initialConfig.requiredGrant;
        
        if (requiredGrant) {
            // TODO: multiple + sigular/plural text
            action.setDisabled(!grants[requiredGrant]);
        }
    },
    
    /**
     * Calls the specified function for each action
     * 
     * @param {Function} fn The function to call. The action is passed as the first parameter.
     * Returning <tt>false</tt> aborts and exits the iteration.
     * @param {Object} scope (optional) The scope in which to call the function (defaults to the action).
     */
    each: function(fn, scope) {
        for (var i=0; i<this.actions.length; i++) {
            if (fn.call(scope||this.actions[i], this.actions[i]) === false) break;
        }
    },
    
    /**
     * calculats the grants sum of the given record(s)
     * 
     * @param  {Array}  records
     * @return {Object} grantName: sum
     */
    getGrantsSum: function(records) {

        var defaultGrant = records.length == 0 ? false : true;
        var grants = {
            addGrant:    defaultGrant,
            adminGrant:  defaultGrant,
            deleteGrant: defaultGrant,
            editGrant:   defaultGrant,
            readGrant:   defaultGrant
        };
        
        var recordGrants;
        for (var i=0; i<records.length; i++) {
            recordGrants = this.containerProperty ? 
                records[i].get(this.containerProperty)[this.grantsProperty] : this.grantsProperty ? 
                records[i].get(this.grantsProperty) : records[i].data;
            
            for (var grant in grants) {
                if (grants.hasOwnProperty(grant)) {
                    grants[grant] = grants[grant] & recordGrants[grant];
                }
            }
        }
        
        return grants;
    }
 };
 
/**
 * sets text and disabled status of a set of actions according to the grants 
 * of a set of records
 * @legacy use ActionUpdater Obj. instead!
 * 
 * @param {Array|SelectionModel} records
 * @param {Array|Toolbar}        actions
 * @param {String}               containerField
 * @param {Bool}                 skipGrants evaluation
 */
Tine.widgets.actionUpdater = function(records, actions, containerField, skipGrants) {
    if (!containerField) {
        containerField = 'container_id';
    }

    if (typeof(records.getSelections) == 'function') {
        records = records.getSelections();
    } else if (typeof(records.beginEdit) == 'function') {
        records = [records];
    }
    
    // init grants
    var defaultGrant = records.length == 0 ? false : true;
    var grants = {
        addGrant:    defaultGrant,
        adminGrant:  defaultGrant,
        deleteGrant: defaultGrant,
        editGrant:   defaultGrant,
        readGrant:   defaultGrant
    };
    
    // calculate sum of grants
    for (var i=0; i<records.length; i++) {
        var recordGrants = records[i].get(containerField) ? records[i].get(containerField).account_grants : {};
        for (var grant in grants) {
            grants[grant] = grants[grant] & recordGrants[grant];
        }
    }
    
    /**
     * action iterator
     */
    var actionIterator = function(action) {
        var initialConfig = action.initialConfig;
        if (initialConfig) {
            
            // happens for direct extensions of button class, like the notes button
            if (action.requiredGrant) {
                initialConfig = {
                    requiredGrant: action.requiredGrant,
                    allowMultiple: action.allowMultiple,
                    singularText: action.singularText,
                    pluralText: action.pluralText,
                    translationObject: action.translationObject
                };
            }
            
            // NOTE: we don't handle add action for the moment!
            var requiredGrant = initialConfig.requiredGrant;
            if (requiredGrant && requiredGrant != 'addGrant') {
                var enable = skipGrants || grants[requiredGrant];
                if (records.length > 1 && ! initialConfig.allowMultiple) {
                    enable = false;
                }
                if (records.length == 0) {
                    enable = false;
                }
                
                action.setDisabled(!enable);
                if (initialConfig.singularText && initialConfig.pluralText && initialConfig.translationObject) {
                    var text = initialConfig.translationObject.n_(initialConfig.singularText, initialConfig.pluralText, records.length);
                    action.setText(text);
                }
            }
        }
    };
    
    /**
     * call action iterator
     */
    switch (typeof(actions)) {
        case 'object':
            if (typeof(actions.each) == 'function') {
                actions.each(actionIterator, this);
            } else {
                for (var action in actions) {
                    actionIterator(actions[action]);
                }
            }
        break;
        case 'array':
            for (var i=0; i<actions.length; i++) {
                actionIterator(actions[i]);
            }
        break;
    }
    
};