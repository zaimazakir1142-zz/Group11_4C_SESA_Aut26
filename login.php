<?php require 'includes/layout.php';
$err='';$reg=($_GET['mode']??'')=='register';
if($_SERVER['REQUEST_METHOD']=='POST'){
 $role=$_POST['role']??'donor';$email=trim($_POST['email']??'');$pw=$_POST['password']??'';
 if(($_POST['act']??'')=='register'){
  $name=trim($_POST['name']??'');$reg=true;
  if(!$name||!$email||strlen($pw)<6)$err='Name, email and a 6+ character password are required.';
  elseif(!in_array($role,['donor','receiver','volunteer']))$err='Choose Donor, Receiver/NGO or Volunteer.';
  elseif(q("SELECT id FROM users WHERE email=?",[$email])->fetch())$err='This email is already registered.';
  else{q("INSERT INTO users(name,email,password,role,location,status) VALUES(?,?,?,?,?,?)",[$name,$email,password_hash($pw,PASSWORD_DEFAULT),$role,$_POST['location']??'',$role=='receiver'?'pending':'verified']);
   $_SESSION['u']=q("SELECT * FROM users WHERE id=?",[$GLOBALS['pdo']->lastInsertId()])->fetch();header('Location: '.BASE.'/'.$role.'/dashboard.php');exit;}
 }else{
  $u=q("SELECT * FROM users WHERE email=?",[$email])->fetch();
  if(!$u||!password_verify($pw,$u['password'])||$u['role']!=$role)$err='Wrong email, password or role.';
  elseif($u['status']=='blocked')$err='This account is blocked.';
  else{$_SESSION['u']=$u;header('Location: '.BASE.'/'.$role.'/dashboard.php');exit;}
 }}
top($reg?'Join':'Login','public');$sel=$_POST['role']??'donor'; ?>
<div class="login"><form class="f" method="post"><div class="eyebrow">Rescue with RePlate</div><h1 class="page">Where To Rescue</h1><p class="sub">Confirm your account and choose where you want to deliver food.</p>
<?php if($err)echo '<div class="err">'.e($err).'</div>'; ?>
<input type="hidden" name="act" value="<?=$reg?'register':'login'?>"><label>Select your role</label>
<div class="roles"><?php foreach(['donor'=>'Donor','receiver'=>'Receiver / NGO','volunteer'=>'Volunteer','admin'=>'Admin'] as $k=>$v): if($reg&&$k=='admin')continue; ?><label><input type="radio" name="role" value="<?=$k?>" <?=$sel==$k?'checked':''?>><?=$v?></label><?php endforeach; ?></div>
<?php if($reg): ?><label>Name / Organization</label><input name="name" required><?php endif; ?>
<label>Email Address</label><input type="email" name="email" placeholder="you@example.com" required><label>Password</label><input type="password" name="password" placeholder="Enter your password" required>
<label>Delivery Location</label><input id="loc" name="location" placeholder="Enter or detect your location"><p><button type="button" class="ghost sm" onclick="navigator.geolocation.getCurrentPosition(p=>loc.value=p.coords.latitude.toFixed(5)+','+p.coords.longitude.toFixed(5))">📍 Get Current Location</button></p>
<button style="width:100%"><?=$reg?'Create Account':'Login'?> →</button></form>
<div style="text-align:center;align-self:center"><div style="font-size:60px">🍃</div><h2>Hello, Rescuer!</h2><p class="sub"><?=$reg?'Already have an account?':'New here?'?></p><a class="btn ghost" href="?mode=<?=$reg?'login':'register'?>"><?=$reg?'LOGIN':'CREATE ACCOUNT'?></a>
<p class="sub" style="margin-top:24px;font-size:12px">Demo password for all demo accounts: <b>password</b><br>admin@replate.com · donor@replate.com · ngo@replate.com · volunteer@replate.com</p></div></div>
<?php bottom();
