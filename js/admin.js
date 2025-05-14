document.addEventListener('DOMContentLoaded', function() {
    // Function to check if a rule is complete
    function isRuleComplete(ruleElement) {
        const templateId = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[template_id]"]').value;
        const postTypes = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[post_types][]"]').value;
        
        // Check if template and at least one post type are selected
        if (!templateId || !postTypes || postTypes.length === 0) {
            return false;
        }
        
        return true;
    }

    // Function to highlight incomplete fields
    function highlightIncompleteFields(ruleElement) {
        ruleElement.querySelectorAll('.bdtc-field').forEach(field => field.classList.remove('bdtc-field-error'));
        
        const templateField = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[template_id]"]').closest('.bdtc-field');
        const postTypesField = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[post_types][]"]').closest('.bdtc-field');
        
        if (!ruleElement.querySelector('select[name^="bdtc_rules"][name$="[template_id]"]').value) {
            templateField.classList.add('bdtc-field-error');
        }
        
        if (!ruleElement.querySelector('select[name^="bdtc_rules"][name$="[post_types][]"]').value || 
            ruleElement.querySelector('select[name^="bdtc_rules"][name$="[post_types][]"]').value.length === 0) {
            postTypesField.classList.add('bdtc-field-error');
        }
    }

    // Function to show notices
    function showNotice(message, type) {
        const notice = document.getElementById('bdtc-notice');
        if (notice) {
            notice.textContent = message;
            notice.className = 'notice notice-' + type;
            notice.style.display = 'block';
            
            // Hide notice after 5 seconds
            setTimeout(() => {
                notice.style.display = 'none';
            }, 5000);
        }
    }

    // Function to safely get message from response
    function getMessage(data) {
        return (data && data.data && data.data.message) ? data.data.message : '';
    }

    // Settings form handling
    const settingsForm = document.getElementById('bdtc-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const enabledTypes = Array.from(document.querySelectorAll('.bdtc-template-type input[type="checkbox"]:checked'))
                .map(checkbox => checkbox.value);

            fetch(bdtcAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bdtc_update_settings',
                    nonce: document.querySelector('input[name="_wpnonce"]').value,
                    enabled_types: JSON.stringify(enabledTypes)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotice(getMessage(data) || 'Settings saved successfully', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(getMessage(data) || 'Failed to save settings', 'error');
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                showNotice('An error occurred while saving the settings.', 'error');
            });
        });
    }

    // Add new rule
    const addRuleButton = document.getElementById('bdtc-add-rule');
    if (addRuleButton) {
        addRuleButton.addEventListener('click', function() {
            const existingRules = document.querySelectorAll('.bdtc-rule');
            
            if (existingRules.length > 0) {
                const lastRule = existingRules[existingRules.length - 1];
                
                if (!isRuleComplete(lastRule)) {
                    showNotice('Please complete the current rule before adding a new one. Template and at least one post type are required.', 'error');
                    highlightIncompleteFields(lastRule);
                    lastRule.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
            }

            fetch(bdtcAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bdtc_add_rule',
                    nonce: bdtcAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotice(getMessage(data) || 'New rule added successfully', 'success');
                    location.reload();
                } else {
                    showNotice(getMessage(data) || 'Failed to add new rule', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding rule:', error);
                showNotice('An error occurred while adding the rule.', 'error');
            });
        });
    }

    // Delete rule
    document.querySelectorAll('.bdtc-delete-rule').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this rule?')) {
                return;
            }

            const index = this.dataset.index;
            fetch(bdtcAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bdtc_delete_rule',
                    nonce: bdtcAjax.nonce,
                    index: index
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotice(getMessage(data) || 'Rule deleted successfully', 'success');
                    location.reload();
                } else {
                    showNotice(getMessage(data) || 'Failed to delete rule', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting rule:', error);
                showNotice('An error occurred while deleting the rule.', 'error');
            });
        });
    });

    // Save rules
    const saveRulesButton = document.getElementById('bdtc-save-rules');
    if (saveRulesButton) {
        saveRulesButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const rules = [];
            let hasIncompleteRules = false;
            
            document.querySelectorAll('.bdtc-rule').forEach(ruleElement => {
                const templateSelect = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[template_id]"]');
                const postTypesSelect = ruleElement.querySelector('select[name^="bdtc_rules"][name$="[post_types][]"]');
                
                if (!templateSelect.value || !postTypesSelect.value || postTypesSelect.value.length === 0) {
                    hasIncompleteRules = true;
                    highlightIncompleteFields(ruleElement);
                }
                
                // Get all selected post types
                const selectedPostTypes = Array.from(postTypesSelect.selectedOptions).map(option => option.value);
                
                // Create a rule for each selected post type
                selectedPostTypes.forEach(postType => {
                    rules.push({
                        template: templateSelect.value,
                        post_type: postType,
                        user_role: ruleElement.querySelector('select[name^="bdtc_rules"][name$="[user_role]"]').value,
                        tax_term_ids: ruleElement.querySelector('select[name^="bdtc_rules"][name$="[tax_term_ids][]"]').value || [],
                        priority: parseInt(ruleElement.querySelector('input[name^="bdtc_rules"][name$="[priority]"]').value) || 0
                    });
                });
            });

            if (hasIncompleteRules) {
                showNotice('Please complete all rules before saving. Template and at least one post type are required for each rule.', 'error');
                return;
            }

            console.log('Sending rules:', rules); // Debug log

            fetch(bdtcAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bdtc_update_rules',
                    nonce: bdtcAjax.nonce,
                    rules: JSON.stringify(rules)
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // Debug log
                if (data.success) {
                    showNotice(getMessage(data) || 'Rules saved successfully', 'success');
                } else {
                    showNotice(getMessage(data) || 'An error occurred while saving the rules', 'error');
                }
            })
            .catch(error => {
                console.error('Error saving rules:', error);
                showNotice('An error occurred while saving the rules.', 'error');
            });
        });
    }

    // Reset rules
    const resetRulesButton = document.getElementById('bdtc-reset-rules');
    if (resetRulesButton) {
        resetRulesButton.addEventListener('click', function() {
            if (!confirm('Are you sure you want to reset all rules? This action cannot be undone.')) {
                return;
            }

            fetch(bdtcAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bdtc_reset_rules',
                    nonce: bdtcAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotice(getMessage(data) || 'Rules reset successfully', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    showNotice(getMessage(data) || 'Failed to reset rules', 'error');
                }
            })
            .catch(error => {
                console.error('Error resetting rules:', error);
                showNotice('An error occurred while resetting the rules.', 'error');
            });
        });
    }
}); 