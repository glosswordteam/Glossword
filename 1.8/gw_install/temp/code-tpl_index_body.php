<h2><?php $this->_e("GW::1721142234");?></h2>

<?php while ($this->_dRun("GW::2635168800")) : ?>

<div class="inp">
<form accept-charset="utf-8" action="<?php $this->_e("GW::3319714414");?>" enctype="application/x-www-form-urlencoded" id="chooselanguage" method="post">

<input type="hidden" name="arg[target]" value="welcome" /> 

<fieldset>
<label for="arg-il-">
<?php $this->_e("GW::1699272955");?>
</label>
</fieldset>

<div class="center">
<a href="#" class="submitok" onclick="document.forms[0].submit()"><?php $this->_e("GW::722155088");?></a>
</div>

</form>
</div>
<?php $this->_dEndHook(); endwhile; ?>



<?php while ($this->_dRun("GW::1605062298")) : ?>

<div class="inp">
<?php $this->_e("GW::1296780815");?>

<fieldset><?php $this->_e("GW::1044170091");?></fieldset>

<?php $this->_e("GW::1176333705");?>
</div>
<?php $this->_dEndHook(); endwhile; ?>



<?php while ($this->_dRun("GW::1202360912")) : ?>

<div class="inp">
<?php $this->_e("GW::1296780815");?>

<?php $this->_e("GW::246387629");?>

<fieldset>
<?php $this->_e("GW::797892885");?>

<?php while ($this->_dRun("GW::274476904")) : ?>

<script type="text/javascript">
function INSTALL_show_hide(id)
{
	d = document.getElementById(id).style;
	d.display = (d.display == 'none' || d.display == '') ? 'block' : 'none';
}
</script>
<div class="sequence">
<ul>
<li class="li-header">
<dl>
<dt><?php $this->_e("GW::2870564523");?></dt>
<dd class="value"><?php $this->_e("GW::2893320882");?></dd>
<dd class="value"><?php $this->_e("GW::3681788452");?></dd>
<dd class="status"><?php $this->_e("GW::3420381006");?></dd>
</dl>
</li>

<?php while ($this->_dRun("GW::3159023929")) : ?>

<li class="<?php $this->_e("GW::3961077639");?>" onclick="INSTALL_show_hide('descr-<?php $this->_e("GW::3087807925");?>')">
<dl>
<dt><div class="subject"><?php $this->_e("GW::387907648");?></div></dt>
<dd class="value"><?php $this->_e("GW::2600222564");?>&#160;</dd>
<dd class="value"><?php $this->_e("GW::2048340584");?>&#160;</dd>
<dd class="status"><?php $this->_e("GW::1101542460");?></dd>
</dl>
<div class="descr" id="descr-<?php $this->_e("GW::3087807925");?>"><?php $this->_e("GW::3977824884");?></div>
</li>
<?php $this->_dEndHook(); endwhile; ?>


<li class="li-footer">
<dl>
<dt><?php $this->_e("GW::3692979773");?></dt>
<dd class="value">&#160;</dd>
<dd class="value">&#160;</dd>
<dd class="status"><?php $this->_e("GW::2402607128");?></dd>
</dl>
</li>

</ul>
</div>
<?php $this->_dEndHook(); endwhile; ?>



</fieldset>

<?php $this->_e("GW::1176333705");?>
</div>
<?php $this->_dEndHook(); endwhile; ?>


