<?php
/** Decorated wait screen
 *
 * Available variables:
 * $page->timeout
 *      The number of seconds the user is told to wait
 * $page->data['wait_alert']
 *      The message to display in the alert block.
 * $page->data['wait_description']
 *      The message to display below the progressbar.
 */
?>

<?php if (!empty($page->data['wait_alert'])): ?>
<div class="alert alert-block">
    <h4><?php echo $page->data['wait_alert']; ?></h4>
    </div>
<?php endif; ?>

<div class="progress progress-striped active">
  <div class="bar" style="width: 0%;"></div>
</div>

<p><?php echo $page->data['wait_description']; ?>


<style>
.progress {
    width: 100%;
}
</style>






<script>
$(function(){
    // Prepare the data
    var wait = window.jsdata.wait;
    var now = (new Date()).getTime()/1000;
    var dnow = now - wait.now; // `now` offset
    
    // Progressbar updater
    var $progress = $('.progress .bar');
    var update = function(){
        // Calc percentage
        var perc;
        
        if (wait.end){
            var now = (new Date()).getTime()/1000;
            var pnow = now - dnow; // tz-independent `now`
            
            $.extend(wait, {
                total: wait.end - wait.start,
                elapsed: pnow - wait.start,
                remaining: wait.end - pnow
            });
        
            perc = Math.round(100*wait.elapsed/wait.total);
        } else
            perc = Math.round(new Date().getTime()/1000);

        if (perc >= 100)
            window.location.reload();
        perc %= 100;
            
        // Update the display
        $progress.css('width', perc + '%');
        if (wait.end)
            $progress.text(perc + '%');
    };
    
    update();
    setInterval(update, 950);
});
</script>






<?php
# Store the beginning of the standby time
if (empty($_SESSION['wait_started']) || $_SESSION['wait_started'] < $botState->mtime->format('U'))
    $_SESSION['wait_started'] = time();

# Progressbar percentage
$jsdata = array(
    'wait' => array(
            'start' => $_SESSION['wait_started'],
            'end'   => $page->timeout? $_SESSION['wait_started'] + $page->timeout : null,
            'now'   => time(),
    ),
);
echo '<script>window.jsdata = ', json_encode($jsdata), ';', '</script>';
?>
