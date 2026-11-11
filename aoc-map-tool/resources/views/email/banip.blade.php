<h1>New IP Ban</h1>

Hi Administrator,

Someone has been reached to the maximum limit of login attempt. 
<br>
IP : <?php echo $userip?> <br>
Email Address : <?php echo $email?>
<br>
<h3>Activity Log</h3>

<table border="1" width="100%">
    <tr>
        <td>
            User
        </td>
        <td>
            IP
        </td>
        <td>
            Log Info
        </td>
        <td>
            Date Time
        </td>
    </tr>

    <?php 
    foreach($activity as $log) { ?>
        <tr> 
            <td>
                <?php echo $log->user_name?>
            </td>
            <td>
                <?php echo $log->ip_address?>
            </td>
            <td>
                <?php echo $log->log_info?>
            </td>
            <td>
                <?php echo $log->created_at?> 
            </td>
        </tr>
    <?php } ?>
</table>
