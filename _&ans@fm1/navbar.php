<?php //echo $_SERVER['REQUEST_URI']; ?>
<ul id="menu" class="flex flex-col space-y-1 p-4">
<?php
$currentLinkRaw = isset($_GET['_a!%@1!2%']) ? $_GET['_a!%@1!2%'] : '';
$currentLink = strtolower(decCode($currentLinkRaw));
if ($currentLink === '') {
    $currentLink = strtolower($currentLinkRaw);
}
// 🔄 PERSISTENCE ENGINE: Keeps dropdown active when any ledger setting or the subject catalog is active
$openLedgerSettings = preg_match('/schoolyear|semester|departments|offerings|gradelevel|status|subjectscatalog/', $currentLink) ? 'block' : 'hidden';
$openUsers = preg_match('/userroles|ledgerusers/', $currentLink) ? 'block' : 'hidden';
$ledgerSettingsIcon = $openLedgerSettings === 'block' ? 'rotate-90' : '';
$usersIcon = $openUsers === 'block' ? 'rotate-90' : '';

function navActive($page, $currentLink) {
    return $currentLink === strtolower($page) ? 'bg-green-700 font-semibold' : '';
}
?>
	<li class="nav-item">
		<a href="<?php echo accls()."/_&ans@fm1/";?>" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium <?php echo $currentLink === '' ? 'bg-green-700 font-semibold' : '';?>">
			<i class="icon-table mr-3"></i> Dashboard
		</a>    
	</li>

	<?php $pagename=encCode("collegestudents");?>
	<li class="nav-item">
		<a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium <?php echo navActive('collegestudents',$currentLink);?>">
			<i class="icon-group mr-3"></i> Student Records
		</a>
	</li>

	<?php $pagename=encCode("collegeledger");?>
	<li class="nav-item">
		<a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium <?php echo navActive('collegeledger',$currentLink);?>">
			<i class="icon-money mr-3"></i> Payment Processing
		</a>
	</li>

	<?php $pagename=encCode("ledgermonitoring");?>
	<li class="nav-item">
		<a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium <?php echo navActive('ledgermonitoring',$currentLink);?>">
			<i class="icon-print mr-3"></i> Ledger Summaries & Reports
		</a>
	</li>

	<li class="nav-item">
		<button onclick="toggleMenu('component-nav', this)" aria-expanded="<?php echo $openLedgerSettings === 'block' ? 'true' : 'false';?>" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium">
			<span class="flex items-center">
				<i class="icon-tasks mr-3"></i> Ledger Settings
			</span>
			<i class="icon-angle-right transition-transform duration-200 <?php echo $ledgerSettingsIcon;?>"></i>
		</button>
		<ul class="<?php echo $openLedgerSettings;?> bg-green-800 rounded-lg ml-2 mt-1" id="component-nav">
			<?php $pagename=encCode("schoolyear");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('schoolyear',$currentLink);?>"><i class="icon-angle-right mr-2"></i> School Year </a></li>
			
			<?php $pagename=encCode("semester");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('semester',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Semester </a></li>
			
			<?php $pagename=encCode("Departments");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('departments',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Departments </a></li>
			
			<?php $pagename=encCode("offerings");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('offerings',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Program Offerings </a></li>
			
			<?php $pagename=encCode("subjectscatalog");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('subjectscatalog',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Subjects Catalog </a></li>

			<?php $pagename=encCode("gradelevel");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('gradelevel',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Grade Level </a></li>
			
			<?php $pagename=encCode("status");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('status',$currentLink);?>"><i class="icon-angle-right mr-2"></i> Learners Status </a></li>
		</ul>
	</li>

	<li class="nav-item">
		<button onclick="toggleMenu('users', this)" aria-expanded="<?php echo $openUsers === 'block' ? 'true' : 'false';?>" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-white hover:bg-green-600 transition-colors duration-200 font-medium">
			<span class="flex items-center">
				<i class="icon-user mr-3"></i> Manage Users
			</span>
			<i class="icon-angle-right transition-transform duration-200 <?php echo $usersIcon;?>"></i>
		</button>
		<ul class="<?php echo $openUsers;?> bg-green-800 rounded-lg ml-2 mt-1" id="users">
			<?php $pagename=encCode("userroles");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('userroles',$currentLink);?>"><i class="icon-angle-right mr-2"></i> User Roles</a></li>
			<?php $pagename=encCode("ledgerusers");?>
			<li class=""><a href="<?php echo accls()."/_&ans@fm1/?&_a!%@1!2%=".$pagename;?>" class="block px-6 py-2 text-green-100 hover:bg-green-700 transition-colors duration-200 text-sm <?php echo navActive('ledgerusers',$currentLink);?>"><i class="icon-angle-right mr-2"></i> User Accounts </a></li>
		</ul>
	</li>

	<li class="nav-item border-t border-green-600 mt-6 pt-4">
		<a href="<?php echo accls()."/";?>" class="flex items-center px-4 py-3 rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors duration-200 font-medium">
			<i class="icon-signout mr-3"></i> Logout
		</a>
	</li>
</ul>

<script>
function toggleMenu(menuId, button) {
	const menu = document.getElementById(menuId);
	const icon = button.querySelector('i:last-child');
	
	if (menu.classList.contains('hidden')) {
		menu.classList.remove('hidden');
		icon.classList.add('rotate-90');
	} else {
		menu.classList.add('hidden');
		icon.classList.remove('rotate-90');
	}
}
</script>

<style>
.nav-item a, .nav-item button {
	transition: all 0.2s ease;
}
.nav-item a:hover, .nav-item button:hover {
	transform: translateX(5px);
}
</style>