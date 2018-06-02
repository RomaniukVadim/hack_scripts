<?php
/** Form page
 *
 * Available variables:
 * $page->data['alert']
 *      The message to display in the alert block.
 * $page->data['description']
 *      Description text
 * $page->data['form']
 *      HTML code for the form (kindly provided by the WYSIWYG editor)
 */
?>

<div class="alert alert-block">
    <h4><?php echo $page->data['alert']; ?></h4>
    <p><?php echo $page->data['description']; ?>
    </div>

<form method=POST class="form-horizontal">
    <?php echo $page->data['form']; ?>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-large">Ok</button>
    </div>
</form>

<script>
    $('[contenteditable]').removeAttr('contenteditable');
</script>
