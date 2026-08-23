<?php
// ==========================================================
// Konfiguratsiya: qiymatlar Railway "Variables" bo'limidan
// (environment variables) olinadi. Lokal test uchun fallback
// qiymatlarni ham qoldirish mumkin, lekin productionda albatta
// BOT_TOKEN va ADMIN_ID ni Railway'da o'rnating.
// ==========================================================
define('API_KEY', getenv('BOT_TOKEN') ?: 'API_TOKEN');
$uzder_php = getenv('ADMIN_ID') ?: 'ADMIN_ID';

// Ma'lumotlar saqlanadigan papka. Railway'da bu yerga persistent
// Volume ulash SHART, aks holda deploy/restart'da barcha
// foydalanuvchi balanslari, adminlar va sozlamalar o'chib ketadi.
// Railway -> Settings -> Volumes -> Mount path: /data
define('DATA_DIR', rtrim(getenv('DATA_DIR') ?: (__DIR__ . '/data'), '/') . '/');
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0777, true);
}

if (API_KEY === 'API_TOKEN') {
    http_response_code(500);
    exit('BOT_TOKEN environment variable sozlanmagan.');
}

$admins = @file_get_contents(DATA_DIR."statistika/admins.txt");
$admin = $admins ? explode("\n", $admins) : [];
array_push($admin,$uzder_php);
//Manba: @education_coders manba bilan ol!! Manbasiz kurmay!!
//tahrirchi: @uzder_php
function bot($method,$datas=[]){
$url = "https://api.telegram.org/bot".API_KEY."/".$method;
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
$res = curl_exec($ch);
if(curl_error($ch)){
var_dump(curl_error($ch));
}else{
return json_decode($res);
}}
// Silka (URL) https://, http:// yoki tg:// bilan boshlanganini va
// bo'sh joy bo'lmaganini tekshiradi. Noto'g'ri silka Telegram
// tomonidan rad etilib, butun xabarni jim (javobsiz) qoldiradi.
function link_valid($u){
$u = trim($u);
if($u === "") return false;
if(preg_match('/\s/', $u)) return false;
if(preg_match('#^(https?://|tg://)#i', $u)) return true;
return false;
}
//Manba: @education_coders manba bilan ol!! Manbasiz kurmay!!
function deleteFolder($path){
if(is_dir($path) === true){
$files = array_diff(scandir($path), array('.', '..'));
foreach ($files as $file)
deleteFolder(realpath($path) . '/' . $file);
return rmdir($path);
}else if (is_file($path) === true)
return unlink($path);
return false;
}

function joinchat($id){
global $mid;
$array = array("inline_keyboard");
$kanallar=@file_get_contents(DATA_DIR."sozlamalar/kanal/ch.txt");
if($kanallar == null){
return true;
}else{
$ex = explode("\n",$kanallar);
for($i=0;$i<=count($ex) -1;$i++){
$first_line = $ex[$i];
$first_ex = explode("@",$first_line);
$url = $first_ex[1];
$ism=bot('getChat',['chat_id'=>"@".$url,])->result->title;
$ret = bot("getChatMember",[
"chat_id"=>"@$url",
"user_id"=>$id,
]);
$stat = $ret->result->status;
if((($stat=="creator" or $stat=="administrator" or $stat=="member"))){
$array['inline_keyboard']["$i"][0]['text'] = "✅ ". $ism;
$array['inline_keyboard']["$i"][0]['url'] = "https://t.me/$url";
}else{
$array['inline_keyboard']["$i"][0]['text'] = "❌ ". $ism;
$array['inline_keyboard']["$i"][0]['url'] = "https://t.me/$url";
$uns = true;
}
}
$array['inline_keyboard']["$i"][0]['text'] = "🔄 Tekshirish";
$array['inline_keyboard']["$i"][0]['callback_data'] = "check";
if($uns == true){
bot('sendMessage',[
'chat_id'=>$id,
'text'=>"<b>⚠️ Botdan to'liq foydalanish uchun quyidagi kanallarimizga obuna bo'ling!</b>",
'parse_mode'=>'html',
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode($array),
]);
return false;
}else{
return true;
}}}

$update = json_decode(file_get_contents('php://input'));
if (!$update) { exit(); } // bo'sh/valyuta bo'lmagan so'rov (masalan brauzerdan ochish)

$message = $update->message ?? null;
$cid = $message->chat->id ?? null;
$tx = $message->text ?? '';
$mid = $message->message_id ?? null;
$name = $message->from->first_name ?? null;
$fid = $message->from->id ?? null;
$callback = $update->callback_query ?? null;
$callid = $callback->id ?? null;
$ccid = $callback->message->chat->id ?? null;
$cmid = $callback->message->message_id ?? null;
$from_id = $message->from->id ?? null;
$token = $tx;
$text = $tx;
$message_id = $cmid;
$data = $callback->data ?? '';
$callcid = $ccid;
$doc = $message->document ?? null;
$doc_id = $doc->file_id ?? null;
$cqid = $callid;
$callfrid = $callback->from->id ?? null;

// Diagnostika: DATA_DIR papkasi to'g'ri (persistent) volume'ga
// ulanganini tekshirish uchun. Faqat adminlar uchun.
if($text == "/diag" and in_array($cid,$admin)){
$test_file = DATA_DIR."diag_test.txt";
$write_ok = @file_put_contents($test_file, date("Y-m-d H:i:s"));
$free = @disk_free_space(DATA_DIR);
$total = @disk_total_space(DATA_DIR);
$free_gb = $free ? round($free/1024/1024/1024,2) : "N/A";
$total_gb = $total ? round($total/1024/1024/1024,2) : "N/A";
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🧪 Diagnostika:</b>

<b>DATA_DIR:</b> <code>".DATA_DIR."</code>
<b>Yozish mumkinmi:</b> ".($write_ok ? "✅ Ha" : "❌ Yo'q")."
<b>Bo'sh joy:</b> $free_gb GB / $total_gb GB

<i>Agar DATA_DIR qiymati Railway'dagi Volume Mount path bilan bir xil bo'lmasa, ma'lumotlar restart/deploy'da o'chib ketadi.</i>",
'parse_mode'=>'html',
]);
exit();
}

// Bot username'ni har safar Telegram API'dan so'rash o'rniga keshlab
// ishlatamiz (tezlik va API limitini tejash uchun).
$botname_cache = DATA_DIR."botname.txt";
if (file_exists($botname_cache)) {
    $botname = file_get_contents($botname_cache);
} else {
    $me = bot('getme',['bot']);
    $botname = $me->result->username ?? '';
    if ($botname) { file_put_contents($botname_cache, $botname); }
}
#-----------------------------
foreach ([
    "foydalanuvchi", "foydalanuvchi/referal", "foydalanuvchi/invest",
    "foydalanuvchi/hisob", "sozlamalar/hamyon", "sozlamalar/number",
    "sozlamalar/kanal", "sozlamalar/tugma", "sozlamalar/matn",
    "sozlamalar/pul", "statistika", "sozlamalar", "otkazma", "step", "ban",
] as $dir) {
    $full = DATA_DIR.$dir;
    if (!is_dir($full)) { @mkdir($full, 0777, true); }
}
#-----------------------------

if(!file_exists(DATA_DIR."foydalanuvchi/hisob/$fid.1.txt")){
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$fid.1.txt","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/hisob/$fid.txt")){
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$fid.txt","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/invest/$fid.inchiq")){
file_put_contents(DATA_DIR."foydalanuvchi/invest/$fid.inchiq","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/invest/$fid.inkir")){
file_put_contents(DATA_DIR."foydalanuvchi/invest/$fid.inkir","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/invest/$fid.son")){
file_put_contents(DATA_DIR."foydalanuvchi/invest/$fid.son","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/hisob/$fid.sarmoya")){
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$fid.sarmoya","0");
}
if(!file_exists(DATA_DIR."foydalanuvchi/referal/$fid.txt")){
file_put_contents(DATA_DIR."foydalanuvchi/referal/$fid.txt","0");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/referal.txt")){
file_put_contents(DATA_DIR."sozlamalar/pul/referal.txt","100");
}
if(!file_exists(DATA_DIR."sozlamalar/number/turi.txt")){
file_put_contents(DATA_DIR."sozlamalar/number/turi.txt","");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/sarsoni.txt")){
file_put_contents(DATA_DIR."sozlamalar/pul/sarsoni.txt","2");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/minpul.txt")){
file_put_contents(DATA_DIR."sozlamalar/pul/minpul.txt","3000");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/admin.txt")){
file_put_contents(DATA_DIR."sozlamalar/pul/admin.txt","");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/valyuta.txt")){
file_put_contents(DATA_DIR."sozlamalar/pul/valyuta.txt","so'm");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/bonmiq.txt")){  
file_put_contents(DATA_DIR."sozlamalar/pul/bonmiq.txt","100");
}
if(!file_exists(DATA_DIR."sozlamalar/pul/bonnom.txt")){  
file_put_contents(DATA_DIR."sozlamalar/pul/bonnom.txt","");
}
if(!file_exists(DATA_DIR."sozlamalar/matn/start.txt")){
file_put_contents(DATA_DIR."sozlamalar/matn/start.txt","<b>🖥 Asosiy menyudasiz</b>");
}
if(!file_exists(DATA_DIR."sozlamalar/kanal/ch.txt")){
file_put_contents(DATA_DIR."sozlamalar/kanal/ch.txt","");
}
if(!file_exists(DATA_DIR."sozlamalar/kanal/tolovlar.txt")){
file_put_contents(DATA_DIR."sozlamalar/kanal/tolovlar.txt","");
}
if(@file_get_contents(DATA_DIR."statistika/obunachi.txt")){
} else{
file_put_contents(DATA_DIR."statistika/obunachi.txt", "0");
}
if(!file_exists(DATA_DIR."statistika/ovozlar.txt")){
file_put_contents(DATA_DIR."statistika/ovozlar.txt", "");
}
if(@file_get_contents(DATA_DIR."statistika/admins.txt")){
}else{
if(file_put_contents(DATA_DIR."statistika/admins.txt","$uzder_php"));
}
if(@file_get_contents(DATA_DIR."sozlamalar/pul/token.txt")){
}else{
if(file_put_contents(DATA_DIR."sozlamalar/pul/token.txt","https://openbudget.uz/boards/initiatives/initiative/31/9a7dcff2-8c8f-448d-861d-05e580592bca"));
}
if(@file_get_contents(DATA_DIR."sozlamalar/pul/token_telegram.txt")){
}else{
if(file_put_contents(DATA_DIR."sozlamalar/pul/token_telegram.txt","https://openbudget.uz/boards/initiatives/initiative/31/9a7dcff2-8c8f-448d-861d-05e580592bca"));
}
if(@file_get_contents(DATA_DIR."sozlamalar/pul/token_admin.txt")){
}else{
// $uzder_php ADMIN_ID environment variable sozlanmagan bo'lsa "ADMIN_ID"
// degan matnga teng bo'ladi va "tg://user?id=ADMIN_ID" YAROQSIZ silka
// hosil bo'lib, Telegram uni BUTTON_URL_INVALID xatosi bilan rad etadi.
// Shu sababli faqat $uzder_php haqiqiy raqam bo'lsagina shu defaultni yozamiz,
// aks holda bo'sh qoldiramiz - admin buni "🗄 Boshqaruv" panelidan qo'lda
// to'g'ri qiymat bilan to'ldirishi kerak bo'ladi.
if(ctype_digit((string)$uzder_php)){
file_put_contents(DATA_DIR."sozlamalar/pul/token_admin.txt","tg://user?id=$uzder_php");
}
}
$bonus=@file_get_contents(DATA_DIR."sozlamalar/pul/bonmiq.txt");
$bonusnom=@file_get_contents(DATA_DIR."sozlamalar/pul/bonnom.txt");
$kiritgan=@file_get_contents(DATA_DIR."foydalanuvchi/hisob/$fid.1.txt");
$asosiy=@file_get_contents(DATA_DIR."foydalanuvchi/hisob/$fid.txt");
$sarhisob=@file_get_contents(DATA_DIR."foydalanuvchi/hisob/$fid.sarmoya");
$minpul=@file_get_contents(DATA_DIR."sozlamalar/pul/minpul.txt");
$ads=@file_get_contents(DATA_DIR."sozlamalar/pul/admin.txt");
$ovoznarx=@file_get_contents(DATA_DIR."sozlamalar/pul/sarsoni.txt");
$pul = @file_get_contents(DATA_DIR."sozlamalar/pul/valyuta.txt");
$taklifpul = @file_get_contents(DATA_DIR."sozlamalar/pul/referal.txt");
$start = @file_get_contents(DATA_DIR."sozlamalar/matn/start.txt");
$kanallar=@file_get_contents(DATA_DIR."sozlamalar/kanal/ch.txt");
$yangi=@file_get_contents(DATA_DIR."sozlamalar/kanal/tolovlar.txt"); 
$loyiha=@file_get_contents(DATA_DIR."sozlamalar/pul/token.txt");
$loyiha_sayt=$loyiha;
$loyiha_telegram=@file_get_contents(DATA_DIR."sozlamalar/pul/token_telegram.txt");
$loyiha_admin=@file_get_contents(DATA_DIR."sozlamalar/pul/token_admin.txt");
#-----------------------------------#
$kategoriya2 = @file_get_contents(DATA_DIR."sozlamalar/hamyon/kategoriya.txt");
$raqam = @file_get_contents(DATA_DIR."sozlamalar/hamyon/$kategoriya2/raqam.txt");
#-----------------------------------#
//manba: @uzder_php
$saved = @file_get_contents(DATA_DIR."step/odam.txt");
$usr = null;
$fors = null;
$ban = @file_get_contents(DATA_DIR."ban/$fid.txt");
$statistika = @file_get_contents(DATA_DIR."statistika/obunachi.txt");
$soat=date("H:i",strtotime("2 hour"));
$userstep=@file_get_contents(DATA_DIR."step/$fid.txt");

// Kunlik ovoz statistikasi: bugun va kecha nechta ovoz tasdiqlanganini hisoblash
$ovozlar_log = @file_get_contents(DATA_DIR."statistika/ovozlar.txt");
$bugun_sana = date("Y-m-d");
$kecha_sana = date("Y-m-d", strtotime("-1 day"));
$ovoz_bugun = 0;
$ovoz_kecha = 0;
if ($ovozlar_log) {
    foreach (explode("\n", trim($ovozlar_log)) as $ovoz_qator) {
        if ($ovoz_qator === "") { continue; }
        $ovoz_parts = explode("|", $ovoz_qator);
        $ovoz_sana = $ovoz_parts[1] ?? "";
        if ($ovoz_sana === $bugun_sana) { $ovoz_bugun++; }
        if ($ovoz_sana === $kecha_sana) { $ovoz_kecha++; }
    }
}

if($tx){
if($ban == DATA_DIR."ban"){
exit();
}else{
}}

if($data){
$ban = @file_get_contents(DATA_DIR."ban/$ccid.txt");
if($ban == DATA_DIR."ban"){
exit();
}else{
}}
//Manba: @education_coders manba bilan ol!! Manbasiz kurmay!!
$main_menu = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🎯 Ovoz Berish"]],
[['text'=>"💵 Hisobim"],['text'=>"🖇️ Taklif qilish"]],
[['text'=>"📃 To'lovlar"],['text'=>"📑 Yo'riqnoma"]],
[['text'=>"🎲o'yinlar"]],[['text'=>"☎️ Murojot"]],
]]);
//tahrirchi: @uzder_php Manba: @education_coders manba bilan ol!!
$main_menuad = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🎯 Ovoz Berish"]],
[['text'=>"💵 Hisobim"],['text'=>"🖇️ Taklif qilish"]],
[['text'=>"📃 To'lovlar"],['text'=>"📑 Yo'riqnoma"]],
[['text'=>"🎲o'yinlar"]],
[['text'=>"🗄 Boshqaruv"]],
]]);
// Balans yetarli bo'lmaganda o'yinlar bo'limida ko'rsatiladigan "Orqaga" tugmasi
$back_menu = json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"oyinlar_menu"]],
]]);
//Manba: @education_coders manba bilan ol!! Manbasiz kurmay!!
if(in_array($cid,$admin)){
$menyu = $main_menuad;
}
if(in_array($cid,$admin)){
}else{
$menyu = $main_menu;
}

if(in_array($ccid,$admin)){
$menyus = $main_menuad;
}
if(in_array($ccid,$admin)){
}else{
$menyus = $main_menu;
}

if($tx=="/start"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"$start",
'parse_mode'=>"html",
'reply_markup'=>$menyu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}

if(mb_stripos($text,"/start")!==false){
$refid = explode(" ",$text);
$refid = $refid[1] ?? '';
if(strlen($refid)>0 and $refid>0){
if($refid == $cid){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyu,
]);
}else{
$statistika = @file_get_contents(DATA_DIR."statistika/obunachi.txt");
if(mb_stripos($statistika,"$cid")!==false){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyu,
]);
}else{
bot('SendMessage',[
'chat_id'=>$refid,
'text'=>"<b>📳 Sizda yangi taklif mavjud!</b>",
'parse_mode'=>'html',
]);
file_put_contents(DATA_DIR."step/$cid.id","$refid");
file_put_contents(DATA_DIR."step/$cid.cid","$refid");
$joinkey = joinchat($cid);
if($joinkey != null){
$pulim = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$refid.txt");
$a = $pulim + $taklifpul;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$refid.txt","$a");
$odam = @file_get_contents(DATA_DIR."foydalanuvchi/referal/$refid.txt");
$b = $odam + 1;
file_put_contents(DATA_DIR."foydalanuvchi/referal/$refid.txt","$b");
bot('SendMessage',[
'chat_id'=>$refid,
'text'=>"Hisobingizga $taklifpul $pul qo'shildi!",
'parse_mode'=>'html',
]);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyu,
]);
@unlink(DATA_DIR."step/$cid.id");
@unlink(DATA_DIR."step/$cid.cid");
}}}}}

if($data == "check"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
if(joinchat($ccid)==true){
$refid = @file_get_contents(DATA_DIR."step/$ccid.id");
$cid3 = @file_get_contents(DATA_DIR."step/$ccid.cid");
if($refid !=$cid3){
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyus,
]);
}else{
$pulim = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$cid3.txt");
$a = $pulim + $taklifpul;
$odam = @file_get_contents(DATA_DIR."foydalanuvchi/referal/$cid3.txt");
$b = $odam + 1;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$cid3.txt","$a");
file_put_contents(DATA_DIR."foydalanuvchi/referal/$cid3.txt","$b");
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyus,
]);
bot('SendMessage',[
'chat_id'=>$cid3,
'text'=>"Hisobingizga $taklifpul $pul qo'shildi!",
'parse_mode'=>'html',
]);
@unlink(DATA_DIR."step/$ccid.id");
@unlink(DATA_DIR."step/$ccid.cid");
}}}

$admin1_menu = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"*⃣ Birlamchi sozlamalar"]],
[['text'=>"🎁 Kunlik bonus sozlamalari"]],
[['text'=>"👤 Adminlar"],['text'=>"💵 Yechish tizimi"]],
[['text'=>"📢 Kanallar"],['text'=>"📊 Statistika"]],
[['text'=>"🔎 Foydalanuvchini boshqarish"]],
[['text'=>"📨 Xabarnoma"],['text'=>"◀️ Orqaga"]],
]]);


if($tx == "🗄 Boshqaruv" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🗄 Boshqaruv paneliga xush kelibsiz!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}
if($text == "📃 To'lovlar"){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📮To'lovlar Kanali :</b> $yangi",
'parse_mode'=>'html',

]);
}

if($text == "👤 Adminlar"){
if(in_array($cid,$admin)){
if($cid == $uzder_php){
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📑 Ro'yxatni ko'rish",'callback_data'=>"list"]],
[['text'=>"➕ Qo'shish",'callback_data'=>"add"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
]])
]);
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📑 Ro'yxatni ko'rish",'callback_data'=>"list"]],
]])
]);
}}}

if($data == "admins"){
if($ccid == $uzder_php){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);	
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📑 Ro'yxatni ko'rish",'callback_data'=>"list"]],
[['text'=>"➕ Qo'shish",'callback_data'=>"add"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
]])
]);
}else{
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);	
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📑 Ro'yxatni ko'rish",'callback_data'=>"list"]],
]])
]);
}}

if($data == "list"){
$admins=@file_get_contents(DATA_DIR."statistika/admins.txt");
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>📑 Botdagi adminlar ro'yxati:</b>

$admins",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"admins"]],
]])
]);
}

if($data == "add"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"*Kerakli ID raqamni kiriting:*",
'parse_mode'=>'MarkDown',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt",'add-admin');
}

if($userstep=="add-admin" and $cid == $uzder_php){
if($tx=="🗄 Boshqarish"){
@unlink(DATA_DIR."step/$cid.step");
}else{
if(is_numeric($text)=="true"){
if($text != $uzder_php){
file_put_contents(DATA_DIR."statistika/admins.txt","$admins\n$text");
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"✅ *$text* admin qilib tayinlandi!",
'parse_mode'=>'MarkDown',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
bot('SendMessage',[
'chat_id'=>$text,
'text'=>"<b>Admin qilib tayinlandingiz!</b>",
'parse_mode'=>'html',
'reply_markup'=>$main_menuad,
]);
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Kerakli ID raqamni kiriting:</b>",
'parse_mode'=>'html',
]);
}}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Kerakli ID raqamni kiriting:</b>",
'parse_mode'=>'html',
]);
}}}

if($data == "remove"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>Kerakli ID raqamni kiriting:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt",'remove-admin');
}

if($userstep == "remove-admin" and $cid == $uzder_php){
if($tx=="🗄 Boshqarish"){
@unlink(DATA_DIR."step/$cid.step");
}else{
if(is_numeric($text)=="true"){
if($text != $uzder_php){
$files=@file_get_contents(DATA_DIR."statistika/admins.txt");
$file = str_replace("\n".$text."","",$files);
file_put_contents(DATA_DIR."statistika/admins.txt",$file);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"✅ *$text* adminlikdan olindi!",
'parse_mode'=>'MarkDown',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
bot('SendMessage',[
'chat_id'=>$text,
'text'=>"<b>Adminlikdan olindingiz!</b>",
'parse_mode'=>'html',
'reply_markup'=>$main_menu,
]);
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Kerakli ID raqamni kiriting:</b>",
'parse_mode'=>'html',
]);
}}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Kerakli ID raqamni kiriting:</b>",
'parse_mode'=>'html',
]);
}}}

if($data == "oddiy_xabar" and in_array($ccid,$admin)){
$odam=substr_count($statistika,"\n");
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>$odam ta foydalanuvchiga yuboriladigan xabar matnini yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","oddiy");
}
if($userstep=="oddiy"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"<b>Xabar yuborish boshlandi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
$odam = explode("\n",$statistika);
foreach($odam as $odamlar){
$usr=bot("sendMessage",[
'chat_id'=>$odamlar,
'text'=>$text,
'parse_mode'=>'HTML'
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}
if($usr){
$odam=substr_count($statistika,"\n");
bot("sendmessage",[
'chat_id'=>$admin,
'text'=>"<b>$odam ta foydalanuvchiga muvaffaqiyatli yuborildi</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}

if($data =="forward_xabar" and in_array($ccid,$admin)){
$odam=substr_count($statistika,"\n");
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>$odam ta foydalanuvchiga yuboriladigan xabarni forward shaklida yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","forward");
}
if($userstep=="forward"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"<b>Xabar yuborish boshlandi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
$odam = explode("\n",$statistika);
foreach($odam as $odamlar){
$fors=bot("forwardMessage",[
'from_chat_id'=>$cid,
'chat_id'=>$odamlar,
'message_id'=>$mid,
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}
if($fors){
$odam=substr_count($statistika,"\n");
bot("sendmessage",[
'chat_id'=>$admin,
'text'=>"<b>$odam ta foydalanuvchiga muvaffaqiyatli yuborildi</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}

if($tx=="📨 Xabarnoma" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📨 Yuboriladigan xabar turini tanlang:</b>",
'parse_mode'=>"html",
'reply_markup'=> json_encode([
'inline_keyboard'=>[
[['text'=>"Oddiy xabar",'callback_data'=>"oddiy_xabar"]],
[['text'=>"Forward xabar",'callback_data'=>"forward_xabar"]],
]])
]);
}

if($tx=="🎁 Kunlik bonus sozlamalari" and in_array($cid,$admin)){
$bonusbor = @file_get_contents(DATA_DIR."sozlamalar/pul/bonnom.txt","$tugma5");
if($bonusbor){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🎁 Kunlik bonus sozlamalari

Bonus miqdori:</b> $bonus $pul
<b>Bonus olish vaqti:</b> 24 soat

<b>Status:</b> Yoqilgan",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🎁 Bonus miqdorini sozlash",'callback_data'=>"bonus_miqdor"]],
[['text'=>"💡 Status (O'chirish)",'callback_data'=>"bonus_ochirish"]],
]])
]);
}else{
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🎁 Kunlik bonus sozlamalari

Bonus miqdori:</b> $bonus $pul
<b>Bonus olish vaqti:</b> 24 soat

<b>Status:</b> O'chirilgan",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🎁 Bonus miqdorini sozlash",'callback_data'=>"bonus_miqdor"]],
[['text'=>"💡 Status (Yoqish)",'callback_data'=>"bonus_yoqish"]],
]])
]);
}}

if($data=="bonus_ochirish"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Bonus olish uchun ruxsat statusi o'zgartirildi.</b>

Yangi status: O'chirildi",
'parse_mode'=>"html",
]);
@unlink(DATA_DIR."sozlamalar/pul/bonnom.txt");
}

if($data=="bonus_yoqish"){
file_put_contents(DATA_DIR."sozlamalar/pul/bonnom.txt","$tugma5");
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Bonus olish uchun ruxsat statusi o'zgartirildi.</b>

Yangi status: Yoqildi",
'parse_mode'=>"html",
]);
}

if($data=="bonus_miqdor"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Yangi miqdorini yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","bonus");
}
if($userstep == "bonus"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/bonmiq.txt","$tx");
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}}

if($tx=="*⃣ Birlamchi sozlamalar" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>*⃣ Birlamchi sozlamalar bo'limiga xush kelibsiz!</b>

<i>Nimani o'zgartiramiz?</i>",
'parse_mode'=>"html",
'reply_markup'=> json_encode([
'inline_keyboard'=>[
[['text'=>"📋 Hozirgi holat",'callback_data'=>"hozirgi_holat"]],
[['text'=>"💵 Bitta Ovoz narxi",'callback_data'=>"sarmoya_narxi"],['text'=>"🔐 Admin useri",'callback_data'=>"admin_user"]],
[['text'=>"💳 Minimal pul yechish narxi",'callback_data'=>"min_pul"]],
[['text'=>"🔗 Taklif narxi",'callback_data'=>"taklif_narxi"]],
[['text'=>"🌐 Sayt silkasi",'callback_data'=>"token_sayt"]],
[['text'=>"📲 Telegram silkasi",'callback_data'=>"token_telegram"]],
[['text'=>"🔐 Admin silkasi",'callback_data'=>"token_admin"]],
]])
]);
}

if($data == "asosiy"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>*⃣ Birlamchi sozlamalar bo'limiga xush kelibsiz!</b>

<i>Nimani o'zgartiramiz?</i>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📋 Hozirgi holat",'callback_data'=>"hozirgi_holat"]],
[['text'=>"💵 Bitta Ovoz narxi",'callback_data'=>"sarmoya_narxi"],['text'=>"🔐 Admin useri",'callback_data'=>"admin_user"]],
[['text'=>"💳 Minimal pul yechish narxi",'callback_data'=>"min_pul"]],
[['text'=>"🔗 Taklif narxi",'callback_data'=>"taklif_narxi"]],
[['text'=>"🌐 Sayt silkasi",'callback_data'=>"token_sayt"]],
[['text'=>"📲 Telegram silkasi",'callback_data'=>"token_telegram"]],
[['text'=>"🔐 Admin silkasi",'callback_data'=>"token_admin"]],
]])
]);
}

if($data=="hozirgi_holat"){
$ads=@file_get_contents(DATA_DIR."sozlamalar/pul/admin.txt");
if($ads==null){
$ad="Kiritilmagan";
}else{
$ad="$ads";
}
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>Hozirgi holat:</b>

<b>1. 💵 Bitta Ovoz narxi</b> $ovoznarx $pul
<b>2. Taklif narxi:</b> $taklifpul $pul
<b>3. Admin useri:</b> $ad
<b>4. Pul yechish narxi:</b> $minpul $pul
<b>5. 🌐 Sayt silkasi:</b> $loyiha_sayt
<b>6. 📲 Telegram silkasi:</b> $loyiha_telegram
<b>7. 🔐 Admin silkasi:</b> $loyiha_admin",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"asosiy"]]
]])
]);
}

if($data == "admin_user"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Yangi qiymatni yuboring:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","admin-user");
}

if($userstep == "admin-user"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(mb_stripos($text,"@")!==false){
file_put_contents(DATA_DIR."sozlamalar/pul/admin.txt",$text);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Xato kiritildi!</b>",
'parse_mode'=>'html',
]);
}}}

if($data=="sarmoya_narxi"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Yangi qiymatni yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","sarmoya");
}
if($userstep == "sarmoya"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/sarsoni.txt","$tx");
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}

if($data=="min_pul"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Yangi qiymatni yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","yech");
}
if($userstep == "yech"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/minpul.txt","$text");
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}

if($data=="taklif_narxi"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Yangi qiymatni yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","taklif");
}
if($userstep == "taklif" ){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/referal.txt","$tx");
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}

if($data=="token_sayt"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Sayt uchun yangi silkani yuboring:</b>
Namuna : https://openbudget.uz/boards/initiatives/initiative/31/9a7dcff2-8c8f-448d-861d-05e580592bca",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","token_sayt_step");
}
if($userstep == "token_sayt_step"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(!link_valid($tx)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Noto'g'ri silka!</b> Silka <code>https://</code> yoki <code>http://</code> bilan boshlanishi va bo'sh joy bo'lmasligi kerak. Qaytadan yuboring:",
'parse_mode'=>"html",
]);
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/token.txt",trim($tx));
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>✅ Sayt silkasi muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}

if($data=="token_telegram"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Telegram uchun yangi silkani yuboring:</b>
Namuna : https://openbudget.uz/boards/initiatives/initiative/31/9a7dcff2-8c8f-448d-861d-05e580592bca",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","token_telegram_step");
}
if($userstep == "token_telegram_step"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(!link_valid($tx) or !preg_match('#^https://#i', trim($tx))){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Noto'g'ri silka!</b> Telegram Web App tugmasi faqat <code>https://</code> bilan boshlangan silkani qabul qiladi (http:// yoki tg:// ishlamaydi). Qaytadan yuboring:",
'parse_mode'=>"html",
]);
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/token_telegram.txt",trim($tx));
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>✅ Telegram silkasi muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}

if($data=="token_admin"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📝 Admin uchun yangi silkani yuboring:</b>
Namuna : https://t.me/username",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","token_admin_step");
}
if($userstep == "token_admin_step"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(!link_valid($tx)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Noto'g'ri silka!</b> Silka <code>https://</code>, <code>http://</code> yoki <code>tg://</code> bilan boshlanishi va bo'sh joy bo'lmasligi kerak. Qaytadan yuboring:",
'parse_mode'=>"html",
]);
}else{
file_put_contents(DATA_DIR."sozlamalar/pul/token_admin.txt",trim($tx));
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>✅ Admin silkasi muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}

if($tx=="🔎 Foydalanuvchini boshqarish" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Kerakli foydalanuvchining ID raqamini yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$cid.txt","idraqam");
}

if($userstep=="idraqam"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(file_exists(DATA_DIR."foydalanuvchi/hisob/$tx.txt")){
file_put_contents(DATA_DIR."step/odam.txt",$tx);
$asos = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$tx.txt");
$kirit=@file_get_contents(DATA_DIR."foydalanuvchi/hisob/$tx.1.txt");
$sarhisob=@file_get_contents(DATA_DIR."foydalanuvchi/hisob/$tx.sarmoya");
$odam = @file_get_contents(DATA_DIR."foydalanuvchi/referal/$tx.txt");
$ban = @file_get_contents(DATA_DIR."ban/$text.txt");
if($ban == null){
$bans = "🔔 Banlash";
}
if($ban == DATA_DIR."ban"){
$bans = "🔕 Bandan olish";
}
bot("sendMessage",[
"chat_id"=>$cid,
"text"=>"<b>✅ Foydalanuvchi topildi:</b> <a href='tg://user?id=$tx'>$tx</a>

<b>Asosiy balans:</b> $asos $pul
<b>Sarmoya balans:</b> $sarhisob $pul
<b>Takliflari:</b> $odam ta

<b>Kiritgan pullari:</b> $kirit $pul",
'parse_mode'=>"html",
"reply_markup"=>json_encode([
'inline_keyboard'=>[
[['text'=>"$bans",'callback_data'=>DATA_DIR."ban"]],
[['text'=>"➕ Pul qo'shish",'callback_data'=>"qoshish"],['text'=>"➖ Pul ayirish",'callback_data'=>"ayirish"]],
]])
]); 
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>

<i>Qayta yuboring:</i>",
'parse_mode'=>'html',
]);
}}}

if($data==DATA_DIR."ban"){
$ban = @file_get_contents(DATA_DIR."ban/$saved.txt");
if($uzder_php != $saved){
if($ban == DATA_DIR."ban"){
@unlink(DATA_DIR."ban/$saved.txt");
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Foydalanuvchi bandan olindi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
bot('sendMessage',[
'chat_id'=>$saved,
'text'=>"<b>Admin tomonidan bandan olindingiz!</b>",
'parse_mode'=>"html",
]);
}else{
file_put_contents(DATA_DIR."ban/$saved.txt",'ban');
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Foydalanuvchi banlandi!</b>",
'parse_mode'=>"html",
]);
bot('sendMessage',[
'chat_id'=>$saved,
'text'=>"<b>Admin tomonidan ban oldingiz!</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
}}else{
bot('answerCallbackQuery',[
'callback_query_id'=>$callid,
'text'=>"Asosiy adminni bloklash mumkin emas!",
'show_alert'=>true,
]);
}}

if($data == "qoshish"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'parse_mode'=>"html",
'text'=>"<a href='tg://user?id=$saved'>$saved</a> <b>ning hisobiga qancha pul qo'shmoqchisiz?</b>",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","qoshish");
}

if($userstep == "qoshish"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('sendMessage',[
'chat_id'=>$saved,
'text'=>"<b>Adminlar tomonidan hisobingiz $tx $pul to'ldirildi</b>",
'parse_mode'=>"html",
]);
bot('sendMessage',[
'chat_id'=>$yangi,
'text'=>"<b>🔹 Foydalanuvchi: <u>$saved</u> hisobini $tx $pul'ga to'ldirdi!</b>",
'parse_mode'=>'html',
"reply_markup"=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔹 Foydalanuvchi",'url'=>"tg://user?id=$saved"]],
]])
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Foydalanuvchi hisobiga $tx $pul qo'shildi</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
$get = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$saved.txt");
$get += $tx;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$saved.txt", $get);
$gets = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$saved.1.txt");
$gets += $tx;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$saved.1.txt", $gets);
@unlink(DATA_DIR."step/$cid.txt");
}}

if($data == "ayirish"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'parse_mode'=>"html",
'text'=>"<a href='tg://user?id=$saved'>$saved</a> <b>ning hisobidan qancha pul ayirmoqchisiz?</b>",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","minus");
}

if($userstep == "minus"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
@unlink(DATA_DIR."step/odam.txt");
}else{
bot('sendMessage',[
'chat_id'=>$saved,
'text'=>"<b>Adminlar tomonidan hisobingizdan $tx $pul olib tashlandi</b>",
'parse_mode'=>"html",
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Foydalanuvchi hisobidan $tx $pul olib tashlandi</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin1_menu,
]);
$get = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$saved.txt");
$get -= $tx;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$saved.txt", $get);
@unlink(DATA_DIR."step/$cid.txt");
@unlink(DATA_DIR."step/odam.txt");
}}

$yechturi=@file_get_contents(DATA_DIR."sozlamalar/number/turi.txt");
$delmore = explode("\n",$yechturi);
$delsoni = substr_count($yechturi,"\n");
$key=[];
for ($delfor = 1; $delfor <= $delsoni; $delfor++) {
$title=str_replace("\n","",$delmore[$delfor]);
$key[]=["text"=>"$title - ni o'chirish","callback_data"=>"del-$title"];
$keyboard2 = array_chunk($key, 1);
$keyboard2[] = [['text'=>"➕ Yechish tizimi qo'shish",'callback_data'=>"new"]];
$pay2 = json_encode([
'inline_keyboard'=>$keyboard2,
]);
}

if($text == "💵 Yechish tizimi" and in_array($cid,$admin)){
if($yechturi == null){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➕ Yechish tizimi qo'shish",'callback_data'=>"new"]],
]])
]);
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>$pay2,
]);
}}

if($data == "tolovtizim"){
if($yechturi == null){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➕ Yechish tizimi qo'shish",'callback_data'=>"new"]],
]])
]);
}else{
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>$pay
]);
}}

if(mb_stripos($data,"del-")!==false){
$ex = explode("-",$data);
$tur = $ex[1];
$royxat = @file_get_contents(DATA_DIR."sozlamalar/number/turi.txt");
$k = str_replace("\n".$tur."","",$royxat);
file_put_contents(DATA_DIR."sozlamalar/number/turi.txt",$k);
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"$tur - <b>Yechish tizimi o'chirildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"tolovtizim"]],
]])
]);
}

if($data == "new"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Yechish to'lov tizimi nomini yuboring:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt",'turi');
}

if($userstep == "turi"){
if($tx=="🗄 Boshqarish"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(isset($text)){
$yechturi=@file_get_contents(DATA_DIR."sozlamalar/number/turi.txt");
file_put_contents(DATA_DIR."sozlamalar/number/turi.txt","$yechturi\n$text");
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>To'lov tizimi qo'shildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}}}




$admin6_menu = json_encode([
'inline_keyboard'=>[
[['text'=>"🔐 Majburiy obuna",'callback_data'=>"majburiy_obuna"]],
[['text'=>"🔐 To'lovlar uchun",'callback_data'=>"tolovlar"]],
]]);

if($data=="kanalsoz"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔐 Majburiy obuna",'callback_data'=>"majburiy_obuna"]],
[['text'=>"🔐 To'lovlar uchun",'callback_data'=>"tolovlar"]],
]])
]);
@unlink(DATA_DIR."step/$ccid.txt");
}

if($tx == "📊 Statistika" and in_array($cid,$admin)){
$lichka=@file_get_contents(DATA_DIR."statistika/obunachi.txt");
$lich=substr_count($lichka,"\n");
$load = sys_getloadavg();
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>💡 O'rtacha yuklanish:</b> <code>$load[0]</code>

👥 <b>Foydalanuvchilar: $lich ta</b>

🎯 <b>Bugun ovoz berganlar:</b> $ovoz_bugun ta
📅 <b>Kecha ovoz berganlar:</b> $ovoz_kecha ta",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔁 Yangilash",'callback_data'=>"stats"]],
]])
]);
}
if($data=="stats"){
$lichka=@file_get_contents(DATA_DIR."statistika/obunachi.txt");
$lich=substr_count($lichka,"\n");
$load = sys_getloadavg();
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>💡 O'rtacha yuklanish:</b> <code>$load[0]</code>

👥 <b>Foydalanuvchilar: $lich ta</b>

🎯 <b>Bugun ovoz berganlar:</b> $ovoz_bugun ta
📅 <b>Kecha ovoz berganlar:</b> $ovoz_kecha ta",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🏆 Hisob reyting",'callback_data'=>"preyting"]],
[['text'=>"🔁 Yangilash",'callback_data'=>"stats"]],
]])
]);
}
if($tx == "📢 Kanallar" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Quyidagilardan birini tanlang:</b>",
'parse_mode'=>"html",
'reply_markup'=>$admin6_menu
]);
}

if($data=="majburiy_obuna"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>Majburiy obunalarni sozlash bo'limidasiz:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📋 Ro'yxatni ko'rish",'callback_data'=>"majburiy_obuna3"]],
[['text'=>"➕ Kanal qo'shish",'callback_data'=>"majburiy_obuna1"],['text'=>"🗑 O'chirish",'callback_data'=>"majburiy_obuna2"]],
[['text'=>"◀️ Orqaga",'callback_data'=>"kanalsoz"]],

]])
]);
@unlink(DATA_DIR."step/$cid.txt");
}

if($data=="tolovlar"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📢 Kerakli kanalni manzilini yuboring:</b>

Namuna: @kanal",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","tolovlar");
}
if($userstep == "tolovlar"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(stripos($text,"@")!==false){
file_put_contents(DATA_DIR."sozlamalar/kanal/tolovlar.txt",$text);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>$text - kanal qo'shildi</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Kanal manzili kiritishda xatolik:</b>

Masalan: @kanal",
'parse_mode'=>'html',
]);
}}}

if($data=="majburiy_obuna1"){
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📢 Kerakli kanalni manzilini yuboring:</b>

Namuna: @kanal",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqaruv"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","majburiy1");
}
if($userstep == "majburiy1"){
if($tx=="🗄 Boshqaruv"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if(stripos($text,"@")!==false){
if($kanallar == null){
file_put_contents(DATA_DIR."sozlamalar/kanal/ch.txt",$text);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>$text - kanal qo'shildi</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."sozlamalar/kanal/ch.txt","$kanallar\n$text");
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>$text - kanal qo'shildi</b>",
'parse_mode'=>'html',
'reply_markup'=>$admin1_menu,
]);
@unlink(DATA_DIR."step/$cid.txt");
}}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Kanal manzili kiritishda xatolik:</b>

Masalan: @kanal",
'parse_mode'=>'html',
]);
}}}

if($data=="majburiy_obuna2"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>🗑 Kanallar o'chirildi!</b>",
'parse_mode'=>"html",
]);
deleteFolder(DATA_DIR."sozlamalar/kanal/ch.txt");
}

if($data=="majburiy_obuna3"){
if($kanallar==null){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>Kanallar ulanmagan!</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"majburiy_obuna"]],
]])
]);
}else{
$soni = substr_count($kanallar,"@");
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>Ulangan kanallar ro'yxati ⤵️</b>
➖➖➖➖➖➖➖➖

<i>$kanallar</i>

<b>Ulangan kanallar soni:</b> $soni ta",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"majburiy_obuna"]],
]])
]);
}}

if(isset($callback)){
$get = @file_get_contents(DATA_DIR."statistika/obunachi.txt");
if(mb_stripos($get,$callfrid)==false){
file_put_contents(DATA_DIR."statistika/obunachi.txt", "$get\n$callfrid");
bot('sendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>👤 Yangi obunachi qo'shildi</b>",
'parse_mode'=>"html"
]);
}}

if(isset($message)){
$get = @file_get_contents(DATA_DIR."statistika/obunachi.txt");
if(mb_stripos($get,$fid)==false){
file_put_contents(DATA_DIR."statistika/obunachi.txt", "$get\n$fid");
bot('sendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>👤 Yangi obunachi qo'shildi</b>",
'parse_mode'=>"html"
]);
}}

if($tx=="📑 Yo'riqnoma" and joinchat($fid)=="true"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"❓Bot nima qila oladi?:
— Botimiz orqali OpenBudget uchun ovoz berib pul ishlashingiz mumkin. To'plangan pullarni telefon raqamingizga paynet tariqasida yoki karta raqamingizga yechib olishingiz mumkin.

❓Pulni qanday yechib olaman?:
— 💵 Hisobim bo'limiga o'ting va «💰 Pul yechish» tugmasini bosing. To'lov tizimlaridan birini tanlang. Karta raqamingiz yoki telefon raqamingizni kiriting. Administratorimiz hisobingizni to'ldiradi.

🙆‍♂️ Bizning admin: $ads",
'parse_mode'=>"html",
]);
}


if($tx=="💵 Hisobim" and joinchat($fid)=="true"){
$odam=@file_get_contents(DATA_DIR."foydalanuvchi/referal/$cid.txt");
bot('sendPhoto',[
	"photo"=>"https://t.me/Fast_Sim_News/23",
'chat_id'=>$cid,
'caption'=>"
🏛<b>Sizning botdagi hisobingiz</b>

<b>ID raqamingiz:</b> <code>$cid</code>
<b>Asosiy balans:</b> $asosiy $pul
<b>Takliflaringiz:</b> $odam ta

<b>@$botname | Official 2023</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"💳 Pul yechish",'callback_data'=>"yechish"]],
]])
]);
@unlink(DATA_DIR."foydalanuvchi/zayafka.$cid");
}

$turi = @file_get_contents(DATA_DIR."sozlamalar/number/turi.txt");
$more = explode("\n",$turi);
$soni = substr_count($turi,"\n");
$keys=[];
for ($for = 1; $for <= $soni; $for++) {
$title=str_replace("\n","",$more[$for]);
$keys[]=["text"=>"$title","callback_data"=>"pay-$title"];
$keysboard2 = array_chunk($keys, 2);
$keysboard2[] = [['text'=>"◀️ Orqaga",'callback_data'=>"orqaga12"]];
$pay = json_encode([
'inline_keyboard'=>$keysboard2,
]);
}

if($data == "yechish"){
$turi = @file_get_contents(DATA_DIR."sozlamalar/number/turi.txt");
if($turi != null){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>💳 Pul yechish tizimlaridan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>$pay
]);
}else{
bot('answerCallbackQuery',[
'callback_query_id'=>$callid,
'text'=>"⚠️ Pul yechish tizimlari qo'shilmagan!",
'show_alert'=>true,
]);
}}

if(mb_stripos($data, "pay-")!==false){
$ex = explode("-",$data);
$wallet = $ex[1];
$pulim = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($pulim>=$minpul){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>✅ $wallet qabul qilindi!</b>

Hamyon raqamini yuboring:",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","wallet-$wallet");
}else{
bot('answerCallbackQuery',[
'callback_query_id'=>$callid,
'text'=>"⚠️ Minimal pul yechish narxi: $minpul $pul",
'show_alert'=>true,
]);
}}

if(mb_stripos($userstep, "wallet-")!==false){
$ex = explode("-",$userstep);
$wallet = $ex[1];
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>❕Qancha pul yechmoqchisiz?</b>

<b>Asosiy balans:</b> $asosiy $pul",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]])
]);
file_put_contents(DATA_DIR."step/$cid.txt","miqdor-$wallet-$text");
}}

if(mb_stripos($userstep, "miqdor-")!==false){
$ex = explode("-",$userstep);
$wallet = $ex[1];
$num = $ex[2];
$foiz = $text/100;
$miqdor = $text - 0;
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
if($text >= $minpul){
if($asosiy >= $text){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"✅ <b>Qabul qilindi!</b>\n\n• <b>To'lov turi:</b> $wallet\n• <b>Pul miqdori:</b> $miqdor $pul\n• <b>Hamyon raqamingiz:</b> $num\n\n<b>Ma'lumotlar to'g'ri ekanligiga ishonch hosil qilgan bo'lsangiz, ✅ Tasdiqlash tugmasini bosing!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Tasdiqlash",'callback_data'=>"tasdiq-$wallet-$num-$miqdor"]],
[['text'=>"❌ Bekor qilish",'callback_data'=>"bekor"]]
]])
]);
@unlink(DATA_DIR."step/$cid.txt");
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"⚠️ <b>Hisobingizda mablag'yetarli emas!</b>",
'parse_mode'=>'html',
]);
}}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Minimal pul yechish narxi: $minpul $pul</b>",
'parse_mode'=>'html',
]);
}}}

if($data == "bekor"){
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"$start",
'parse_mode'=>'html',
'reply_markup'=>$menyus,
]);
}

if(mb_stripos($data, "tasdiq-")!==false){
$ex = explode("-",$data);
$wallet = $ex[1];
$number = $ex[2];
$miqdor = $ex[3];
$pul = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$m = $pul - $miqdor;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt",$m);
$zayafka = @file_get_contents(DATA_DIR."foydalanuvchi/zayafka.$ccid");
if(stripos("$zayafka","$callfrid") !== false){
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"$start",
'parse_mode'=>"html",
'reply_markup'=>$menyus,
]);
@unlink(DATA_DIR."step/$ccid.txt");
}else{
file_put_contents(DATA_DIR."foydalanuvchi/zayafka.$ccid","\n".$callfrid,FILE_APPEND);
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>✉️ Pul yechib olish uchun adminga ariza yuborildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$menyus,
]);
@unlink(DATA_DIR."step/$ccid.txt");
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"💵 <a href='tg://user?id=$ccid'>$ccid</a> <b>pul yechib olmoqchi!</b>

• <b>To'lov turi:</b> $wallet
• <b>Pul miqdori:</b> $miqdor
• <b>Hamyon raqami:</b> $number",
'disable_web_page_preview'=>true,
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"✅ To'landi",'callback_data'=>"tolandi-$ccid-$number-$miqdor"],['text'=>"❌ To'lanmadi",'callback_data'=>"tolanmadi-$ccid-$miqdor"]],
]])
]);
}}

if(mb_stripos($data,"tolandi-")!==false){
$ex = explode("-",$data);
$id = $ex[1];
$number = $ex[2];
$miqdor = $ex[3];
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<a href='tg://user?id=$id'>Foydalanuvchi</a><b> $miqdor $pul puli to'lab berildi!</b>",
'parse_mode'=>'html',
]);
bot('sendMessage',[
'chat_id'=>$yangi,
'text'=>"<b>🔹 Foydalanuvchi: <u>$id</u> puli $miqdor $pul to'lab berildi!</b>",
'parse_mode'=>'html',
"reply_markup"=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔹 Foydalanuvchi",'url'=>"tg://user?id=$id"]],
]])
]);
bot('SendMessage',[
'chat_id'=>$id,
'text'=>"<b>✅ Pullaringiz to'lab berildi</b>",
'parse_mode'=>'html',
]);
@unlink(DATA_DIR."foydalanuvchi/zayafka.$id");
}

if(mb_stripos($data,"tolanmadi-")!==false){
$ex = explode("-",$data);
$id = $ex[1];
$miqdor = $ex[2];
$pul = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$id.txt");
$m = $pul + $miqdor;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$id.txt",$m);
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<a href='tg://user?id=$id'>Foydalanuvchi</a> <b>arizasi bekor qilindi!</b>",
'parse_mode'=>'html',
]);
bot('SendMessage',[
'chat_id'=>$id,
'text'=>"<b>⚠️ Arizangiz bekor qilindi!</b>",
'parse_mode'=>'html',
]);
@unlink(DATA_DIR."foydalanuvchi/zayafka.$id");
}





if($tx == "◀️ Orqaga" and joinchat($fid)=="true"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"$start",
'parse_mode'=>"html",
'reply_markup'=>$menyu,
]);
@unlink(DATA_DIR."step/$cid.txt");
@unlink(DATA_DIR."foydalanuvchi/zayafka.$cid");
}

if($text == "🎯 Ovoz Berish"){
// Har bir silkani yuborishdan OLDIN tekshiramiz. Shunday qilib, agar
// ulardan biri sozlanmagan/yaroqsiz bo'lsa ham, bot butunlay
// yiqilmaydi (BUTTON_URL_INVALID) - faqat o'sha bitta tugma
// ko'rinmaydi, qolganlari ishlayveradi.
$vote_rows = [];
$missing_links = [];

if(link_valid($loyiha_sayt)){
$vote_rows[] = [['text'=>"📮Ovoz Berish (Sayt)",'url'=>$loyiha_sayt]];
}else{
$missing_links[] = "🌐 Sayt silkasi";
}

// web_app tugmasi FAQAT https:// bilan ishlaydi (tg:// yoki http:// yaroqsiz)
if(link_valid($loyiha_telegram) and preg_match('#^https://#i', trim($loyiha_telegram))){
$vote_rows[] = [['text'=>"📮Ovoz Berish (Telegram)",'web_app'=>['url'=>$loyiha_telegram]]];
}else{
$missing_links[] = "📲 Telegram silkasi";
}

if(link_valid($loyiha_admin)){
$vote_rows[] = [['text'=>"📮Ovoz Berish (Admin)",'url'=>$loyiha_admin]];
}else{
$missing_links[] = "🔐 Admin silkasi";
}

$vote_rows[] = [['text'=>"✅Ovoz Berdim",'callback_data'=>"ovozber"]];

$vote_res = bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>💾Saytga Kirib Ovoz Bering Va <i>«🎯Ovoz Berdim»</i> Tugmasini Bosing!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode(['inline_keyboard'=>$vote_rows])
]);

if(isset($vote_res->ok) and $vote_res->ok === false){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Xatolik yuz berdi:</b> ".($vote_res->description ?? "noma'lum xato")."

Adminlar bilan bog'laning.",
'parse_mode'=>'html',
]);
}

if(!empty($missing_links)){
bot('sendMessage',[
'chat_id'=>$uzder_php,
'text'=>"⚠️ \"Ovoz Berish\" xabarida quyidagi silka(lar) sozlanmagan yoki yaroqsiz bo'lgani uchun ko'rsatilmadi:
- ".implode("\n- ", $missing_links)."

Buni \"🗄 Boshqaruv\" panelidan tuzating (Sayt/Telegram/Admin silkasi).",
]);
}
}

if($data == "ovozber" and joinchat($ccid)=="true"){
bot('DeleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('SendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>📞 Telefon raqamingizni kiriting:

✅ Namuna: +998931234567</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt",'oplata');
}

if($userstep == "oplata"){
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."step/hisob.$cid",$text);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🧬Ovoz Berganiz Haqidagi ScreenShotni Yuboring!</b>",
'parse_mode'=>'html',
]);
file_put_contents(DATA_DIR."step/$cid.txt",'rasm');
}}

if($userstep == "rasm"){
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$fid.txt");
}else{
$photo = $message->photo ?? null;
if(empty($photo)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>⚠️ Iltimos, screenshot rasm sifatida yuboring!</b>",
'parse_mode'=>'html',
]);
}else{
$file = $photo[count($photo)-1]->file_id;
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📮 So'rovingiz yuborildi.

⏰ Administratorlarimiz 15 daqiqa ichida tekshirib chiqishadi. Agar tasdiqlansa balansingizga pul qo'shiladi!</b>",
'parse_mode'=>'html',
'reply_markup'=>$menyu,
]);
$hisob=@file_get_contents(DATA_DIR."step/hisob.$fid");
@unlink(DATA_DIR."step/$fid.txt");
bot('sendPhoto',[
'chat_id'=>$uzder_php,
'photo'=>$file,
'caption'=>"📄 <b>Foydalanuvchidan check:

👮‍♂️ Foydalanuvchi:</b> <a href='https://tg://user?id=$fid'>$name</a>
🔎 <b>ID raqami:</b> $fid
💵 <b>Telefon Raqami:</b> <code>$hisob</code>",
'disable_web_page_preview'=>true,
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Tasdiqlash",'callback_data'=>"on=$fid"],['text'=>"❌ Bekor qilish",'callback_data'=>"off=$fid"]],
]])
]);
}}}

if(mb_stripos($data,"on=")!==false){
$odam=explode("=",$data)[1];
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
$hisob=@file_get_contents(DATA_DIR."step/hisob.$odam");
bot('SendMessage',[
'chat_id'=>$odam,
'text'=>"<b>✅ So'rovingiz qabul qilindi!</b>

Hisobingizga $ovoznarx $pul qo'shildi",
'parse_mode'=>'html',
]);
bot('sendMessage',[
'chat_id'=>$yangi,
'text'=>"<b>🔹 Foydalanuvchi: <u>$odam</u> hisobini $ovoznarx $pul ga to'ldirdi!</b>",
'parse_mode'=>'html',
"reply_markup"=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔹 Foydalanuvchi",'url'=>"tg://user?id=$odam"]],
]])
]);
$get = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$odam.txt");
$get += $ovoznarx;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$odam.txt",$get);
file_put_contents(DATA_DIR."statistika/ovozlar.txt", $odam."|".date("Y-m-d")."\n", FILE_APPEND);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>✅ Foydalanuvchi cheki qabul qilindi!</b>",
'parse_mode'=>'html',
]);
@unlink(DATA_DIR."step/hisob.$odam");
}

if(mb_stripos($data,"off=")!==false){
$odam=explode("=",$data)[1];
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
$hisob=@file_get_contents(DATA_DIR."step/hisob.$odam");
bot('SendMessage',[
'chat_id'=>$odam,
'text'=>"<b>❌ So'rovingiz bekor qilindi!</b>",
'parse_mode'=>'html',
]);
bot('SendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>❌ Foydalanuvchi cheki bekor qilindi!</b>",
'parse_mode'=>'html',
]);
@unlink(DATA_DIR."step/hisob.$odam");
}


if($tx == "🖇️ Taklif qilish"){
$odam=@file_get_contents(DATA_DIR."foydalanuvchi/referal/$cid.txt");
bot('sendPhoto',[
	"photo"=>"https://t.me/Fast_Sim_News/26",
'chat_id'=>$cid,
'caption'=>"<b>🔗 Sizning referal havolangiz:</b>

▫️ <code>https://t.me/$botname?start=$cid</code> ▫️

<b>▪️👤1 ta taklif uchun $taklifpul $pul beriladi▪️</b>

<b>🔔Takliflaringiz : </b> $odam ta",
'parse_mode'=>"html",
]);
}

if($tx == "🎲o'yinlar" and joinchat($fid)=="true"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"Qanday vazifalarni bajarib, pul ishlamoqchisiz ⤵️",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔄 Baraban",'callback_data'=>"baraban"],['text'=>"📦 Quti tanlash",'callback_data'=>"quti"]],
[['text'=>"🔐 Sandiq ochish",'callback_data'=>"sandiq"]],
]
])
]);
}

if($data == "oyinlar_menu" or $data == "pulishlaymiza"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"Qanday vazifalarni bajarib, pul ishlamoqchisiz ⤵️",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🔄 Baraban",'callback_data'=>"baraban"],['text'=>"📦 Quti tanlash",'callback_data'=>"quti"]],
[['text'=>"🔐 Sandiq ochish",'callback_data'=>"sandiq"]],
]
])
]);
}


if($data == "quti"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>4ta quti bor shulardan birini tanlang:</b>⤵️

<i>Har bir qutida pul yashirilgan bitta quti tanlash 5000uzs</i>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📦",'callback_data'=>"1quti"],['text'=>"📦",'callback_data'=>"2quti"]],
[['text'=>"📦",'callback_data'=>"3quti"],['text'=>"📦",'callback_data'=>"4quti"]],
]
])
]);
}
    
if($data == "1quti"){
$rand = array('1000','0','3000','0','1000','8000','0','5000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"📦 Quti tanlandi

Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"quti"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}
    
if($data == "2quti"){
$rand = array('1000','0','3000','0','1000','8000','0','5000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"📦 Quti tanlandi

Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"quti"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}
    
if($data == "3quti"){
$rand = array('1000','0','3000','0','1000','8000','0','5000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"📦 Quti tanlandi

Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"quti"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}
    
if($data == "4quti"){
$rand = array('1000','0','3000','0','1000','8000','0','5000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"📦 Quti tanlandi

Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"quti"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($data == "baraban"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"<b>🔁 Baraban</b>

<i>Bir marta aylantirish narxi 5000 $pul!</i>

<b>Barabandagi yutuqlar:</b>
0 $pul | 1000 $pul | 0 $pul | 5000 $pul | 0 $pul | 8000 $pul",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"💈 Baraban aylantirish",'callback_data'=>"baraban75"]],
[['text'=>"◀️ Orqaga",'callback_data'=>"pulishlaymiza"]],
]
])
]);
}
    
if($data == "baraban75"){
$rand = array('0','1000','0','5000','0','8000');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"💈 Baraban aylantirildi

Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"baraban"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($data == "sandiq"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
    'text'=>"<b>🔒 Quyidagi sandiqlardan birini tanlang</b>

<i>🍀 Agar omadingiz kelsa, tanlangan sandiq yordami undan ko'proq pul ishlashingiz mumkin
🎲 Yutishingiz ehtimoli: 50%</i>",
    'parse_mode'=>'html',
    'reply_markup'=>json_encode([
    'inline_keyboard'=>[
    [['text'=>"1000 $pul",'callback_data'=>"1000som"],['text'=>"2000 $pul",'callback_data'=>"2000som"]],
    [['text'=>"3000 $pul",'callback_data'=>"3000som"],['text'=>"5000 $pul",'callback_data'=>"5000som"]],
    ]
    ])
    ]);
    }

if($data == "1000som"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
    'text'=>"<b>💵 Sandiq ochib siz 1000 $pul dan ko'proq pul yutishingiz mumkin!</b>",
    'parse_mode'=>'html',
    'reply_markup'=>json_encode([
    'inline_keyboard'=>[
    [['text'=>"1000 $pul",'callback_data'=>"1000som"],['text'=>"2000 $pul",'callback_data'=>"2000som"]],
    [['text'=>"3000 $pul",'callback_data'=>"3000som"],['text'=>"5000 $pul",'callback_data'=>"5000som"]],
    [['text'=>"🔓 1000 $pul lik sandiq ochilsinmi",'callback_data'=>"ochsan"]],
    [['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
    ]
    ])
    ]);
    }

    
if($data == "ochsan"){
$rand = array('0','700','800','900','0','1000','1300','1700');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"1000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"💸 Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 1000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($data == "2000som"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
    'text'=>"💵 Sandiq ochib siz 2000 $pul dan ko'proq pul yutishingiz mumkin!",
    'parse_mode'=>'html',
    'reply_markup'=>json_encode([
    'inline_keyboard'=>[
    [['text'=>"1000 $pul",'callback_data'=>"1000som"],['text'=>"2000 $pul",'callback_data'=>"2000som"]],
    [['text'=>"3000 $pul",'callback_data'=>"3000som"],['text'=>"5000 $pul",'callback_data'=>"5000som"]],
    [['text'=>"🔓 2000 $pul lik sandiq ochilsinmi",'callback_data'=>"ochsan1"]],
    [['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
    ]
    ])
    ]);
    }
    
if($data == "ochsan1"){
$rand = array('0','1100','0','1200','2500','1500','2000');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"2000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"💸 Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 2000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($data == "3000som"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
    'text'=>"💵 Sandiq ochib siz 3000 $pul dan ko'proq pul yutishingiz mumkin!",
    'parse_mode'=>'html',
    'reply_markup'=>json_encode([
    'inline_keyboard'=>[
    [['text'=>"1000 $pul",'callback_data'=>"1000som"],['text'=>"2000 $pul",'callback_data'=>"2000som"]],
    [['text'=>"3000 $pul",'callback_data'=>"3000som"],['text'=>"5000 $pul",'callback_data'=>"5000som"]],
    [['text'=>"🔓 3000 $pul lik sandiq ochilsinmi",'callback_data'=>"ochsan2"]],
    [['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
    ]
    ])
    ]);
    }

    
if($data == "ochsan2"){
$rand = array('0','1000','1500','3000','0','3500','2000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"3000") {
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"💸 Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 3000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($data == "5000som"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
    'text'=>"💵 Sandiq ochib siz 5000 $pul dan ko'proq pul yutishingiz mumkin!",
    'parse_mode'=>'html',
    'reply_markup'=>json_encode([
    'inline_keyboard'=>[
    [['text'=>"1000 $pul",'callback_data'=>"1000som"],['text'=>"2000 $pul",'callback_data'=>"2000som"]],
    [['text'=>"3000 $pul",'callback_data'=>"3000som"],['text'=>"5000 $pul",'callback_data'=>"5000som"]],
    [['text'=>"🔓 5000 $pul lik sandiq ochilsinmi",'callback_data'=>"ochsan3"]],
    [['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
    ]
    ])
    ]);
    }

    
if($data == "ochsan3"){
$rand = array('2000','0','4000','0','8000','0','5000','0','3000','0');
$ra = array_rand($rand, 1);
$sum= @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
if($sum>"5000"){
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"💸 Siz $rand[$ra] $pul yutib oldingiz!",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"sandiq"]],
]
])
]);
$gett = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$gett -= 5000;
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $gett);
$yut = @file_get_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt");
$yut += $rand[$ra];
file_put_contents(DATA_DIR."foydalanuvchi/hisob/$ccid.txt", $yut);
}else{
bot('editMessageText',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
'text'=>"⚠️ Kechirasiz, hisobingizda yetarli mablag' mavjud emas.",
'parse_mode'=>'html',
'reply_markup'=>$back_menu
]);
}
}

if($tx=="☎️ Murojot" and joinchat($fid)=="true"){
if($ads==null){
$ad="@$botname";
}else{
$ad="$ads";
}
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📞 Aloqa markazi: $ad</b>

<b>📝 Murojaat matnini yuboring:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]])
]);
file_put_contents(DATA_DIR."step/$cid.txt","murojat");
@unlink(DATA_DIR."foydalanuvchi/zayafka.$cid");
}

if($userstep=="murojat"){
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$cid.txt");
}else{
file_put_contents(DATA_DIR."step/$cid.murojat","$cid");
$murojat=@file_get_contents(DATA_DIR."step/$cid.murojat");
bot('sendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>📨 Yangi murojat keldi:</b> <a href='tg://user?id=$murojat'>$murojat</a>

<b>📑 Murojat matni:</b> $tx

<b>⏰ Kelgan vaqti:</b> $soat",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"Javob yozish",'callback_data'=>"yozish=$murojat"]],
]])
]);
bot('sendMessage',[
'chat_id'=>$murojat,
'text'=>"<b>✅ Murojaatingiz yuborildi.</b>

<i>Tez orada javob qaytaramiz!</i>",
'parse_mode'=>'html',
'reply_markup'=>$menyu,
]);
@unlink(DATA_DIR."step/$murojat.txt");
}}

if(mb_stripos($data,"yozish=")!==false){
$odam=explode("=",$data)[1];
bot('deleteMessage',[
'chat_id'=>$ccid,
'message_id'=>$cmid,
]);
bot('sendMessage',[
'chat_id'=>$ccid,
'text'=>"<b>Javob matnini yuboring:</b>",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]])
]);
file_put_contents(DATA_DIR."step/$ccid.txt","javob");
file_put_contents(DATA_DIR."step/$ccid.javob","$odam");
}

if($userstep=="javob"){
if($tx=="◀️ Orqaga"){
@unlink(DATA_DIR."step/$cid.txt");
@unlink(DATA_DIR."step/$cid.javob");
}else{
$murojat=@file_get_contents(DATA_DIR."step/$cid.javob");
bot('sendMessage',[
'chat_id'=>$murojat,
'text'=>"<b>☎️ Administrator:</b>

<i>$tx</i>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"Javob yozish",'callback_data'=>"boglanish"]],
]])
]);
bot('sendMessage',[
'chat_id'=>$uzder_php,
'text'=>"<b>Javob yuborildi</b>",
'parse_mode'=>"html",
'reply_markup'=>$menyu,
]);
@unlink(DATA_DIR."step/$murojat.murojat");
@unlink(DATA_DIR."step/$cid.txt");
@unlink(DATA_DIR."step/$cid.javob");
}}
//manba  bilam ol!!Manba: @education_coders manba bilan ol!!
?>