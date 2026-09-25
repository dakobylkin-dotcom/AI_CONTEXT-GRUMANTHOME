<?php
/**
 * GrumantHome — v4.8
 * Один файл. Без БД. Без CMS.
 * Готовые шторы и тюль. Без мастерской. Без пошива.
 */

$ORDER_EMAIL = 'dakobylkin@gmail.com';
$ADMIN_PASS  = 'grumant2026';
$MAX_ORDER   = 6;

// Высоты: только до 280 см
$HEIGHTS = ['230','240','250','260','270','280'];

// ─── КАТАЛОГ ───
$PRODUCTS = [
    'len-lenta' => [
        'name'      => 'Тюль под лён на шторной ленте',
        'cat'       => 'lenta',
        'cat_name'  => 'Тюль на шторной ленте',
        'desc'      => 'Мягкая драпировка без утяжеления',
        'type'      => 'lenta',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>1000,  'label'=>'200 см'],
            ['val'=>300, 'price'=>2000,  'label'=>'300 см'],
            ['val'=>400, 'price'=>3900,  'label'=>'400 см'],
        ],
        'colors'    => ['Белый','Серый'],
        'grommets'  => null,
    ],
    'plisse-lenta' => [
        'name'      => 'Тюль-сетка Плиссе на шторной ленте',
        'cat'       => 'lenta',
        'cat_name'  => 'Тюль на шторной ленте',
        'desc'      => 'Воздушная фактура с эффектом плиссе',
        'type'      => 'lenta',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>3900,  'label'=>'200 см'],
            ['val'=>300, 'price'=>4900,  'label'=>'300 см'],
            ['val'=>400, 'price'=>5900,  'label'=>'400 см'],
        ],
        'colors'    => ['Белый','Крем','Серый'],
        'grommets'  => null,
    ],
    'len-luv' => [
        'name'      => 'Тюль под лён на люверсах',
        'cat'       => 'luversy',
        'cat_name'  => 'Тюль на люверсах',
        'desc'      => 'Лён на кольцах, ровная волна',
        'type'      => 'luversy',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>4000,  'label'=>'200 см'],
            ['val'=>300, 'price'=>5000,  'label'=>'300 см'],
        ],
        'colors'    => ['Белый','Серый'],
        'grommets'  => ['Белый','Серебро','Золото'],
    ],
    'vetochki' => [
        'name'      => 'Тюль-сетка «Веточки»',
        'cat'       => 'luversy',
        'cat_name'  => 'Тюль на люверсах',
        'desc'      => 'Узор ветвей, рассеивающий свет',
        'type'      => 'luversy',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>4000,  'label'=>'200 см'],
            ['val'=>300, 'price'=>6000,  'label'=>'300 см'],
        ],
        'colors'    => ['Крем','Капучино','Серый'],
        'grommets'  => ['Белый','Серебро','Золото'],
    ],
    'blackout-lenta' => [
        'name'      => 'Штора Blackout на шторной ленте',
        'cat'       => 'shtory',
        'cat_name'  => 'Шторы',
        'desc'      => 'Плотная ткань для полного затемнения',
        'type'      => 'lenta',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>6000,  'label'=>'200 см'],
        ],
        'colors'    => ['Белый','Серый'],
        'grommets'  => null,
    ],
    'blackout-luv' => [
        'name'      => 'Штора Blackout на люверсах',
        'cat'       => 'shtory',
        'cat_name'  => 'Шторы',
        'desc'      => 'Плотная ткань для полного затемнения',
        'type'      => 'luversy',
        'heights'   => $HEIGHTS,
        'widths'    => [
            ['val'=>200, 'price'=>6000,  'label'=>'200 см'],
        ],
        'colors'    => ['Белый','Серый'],
        'grommets'  => ['Белый','Серебро','Золото'],
    ],
];

// ─── ОСТАТКИ ───
function stock_load() {
    $f = __DIR__ . '/stock.json';
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : [];
}
function stock_save($s) {
    file_put_contents(__DIR__ . '/stock.json', json_encode($s, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function stock_get($pid, $color) {
    $s = stock_load();
    return isset($s[$pid . '|' . $color]) ? (int)$s[$pid . '|' . $color] : 999;
}

// ─── ЦЕНА ПО ПРАЙСУ (сервер не доверяет клиенту) ───
function getPrice($pid, $width) {
    global $PRODUCTS;
    if (!isset($PRODUCTS[$pid])) return 0;
    foreach ($PRODUCTS[$pid]['widths'] as $w) {
        if ($w['val'] == $width) return $w['price'];
    }
    return 0;
}

// ─── ОБРАБОТКА POST ───
$msg = ''; $msgType = '';

// Фидбек
if (isset($_POST['fb_send'])) {
    $line = date('Y-m-d H:i') . ';' . ($_POST['fb_name'] ?? '') . ';' . ($_POST['fb_page'] ?? '') . ';' . str_replace(["
",""], ' ', $_POST['fb_text'] ?? '') . "
";
    file_put_contents(__DIR__ . '/feedback.csv', $line, FILE_APPEND);
    mail($ORDER_EMAIL, 'GrumantHome заметка', $_POST['fb_text'] ?? '');
    $msg = 'Спасибо за заметку!';
    $msgType = 'ok';
}

// Заказ
if (isset($_POST['place_order'])) {
    $name  = trim($_POST['c_name'] ?? '');
    $phone = trim($_POST['c_phone'] ?? '');
    $comment = trim($_POST['c_comment'] ?? '');
    $cart = json_decode($_POST['cart_data'] ?? '[]', true);
    if (!is_array($cart)) $cart = [];

    if ($name === '' || $phone === '') {
        $msg = 'Укажите имя и телефон';
        $msgType = 'err';
    } elseif (count($cart) === 0) {
        $msg = 'Корзина пуста';
        $msgType = 'err';
    } else {
        // Проверка лимита 6 шт
        $totalQty = array_sum(array_column($cart, 'qty'));
        if ($totalQty > $MAX_ORDER) {
            $msg = 'В одном заказе не более ' . $MAX_ORDER . ' штор';
            $msgType = 'err';
        } else {
            // Проверка остатков + пересчёт цен
            $stock = stock_load();
            $ok = true; $err = '';
            foreach ($cart as $i => $item) {
                // Пересчёт цены по прайсу (не доверяем клиенту)
                $realPrice = getPrice($item['id'], $item['width']);
                $cart[$i]['price'] = $realPrice;

                $key = $item['id'] . '|' . $item['color'];
                $avail = isset($stock[$key]) ? (int)$stock[$key] : 999;
                if ($avail !== 999 && $item['qty'] > $avail) {
                    $ok = false;
                    $err = '«' . $item['name'] . '» цвет ' . $item['color'] . ' — осталось: ' . $avail . ' шт.';
                    break;
                }
            }
            if (!$ok) {
                $msg = $err;
                $msgType = 'err';
            } else {
                // Списание
                foreach ($cart as $item) {
                    $key = $item['id'] . '|' . $item['color'];
                    if (isset($stock[$key]) && $stock[$key] !== 999) {
                        $stock[$key] -= $item['qty'];
                        if ($stock[$key] < 0) $stock[$key] = 0;
                    }
                }
                stock_save($stock);

                // Сохранение заказа
                $total = 0;
                $lines = [];
                foreach ($cart as $item) {
                    $sum = $item['price'] * $item['qty'];
                    $total += $sum;
                    $lines[] = $item['name'] . ' (' . $item['color'] . ', ' . $item['width'] . '×' . $item['height'] . ' см' . ($item['grommet'] ? ', люверсы: ' . $item['grommet'] : '') . ') × ' . $item['qty'] . ' = ' . number_format($sum, 0, ',', ' ') . ' ₽';
                }
                $csv = [date('d.m.Y H:i'), $name, $phone, $comment, implode(' | ', $lines), $total];
                $fp = fopen(__DIR__ . '/orders.csv', 'a');
                if ($fp) { fputcsv($fp, $csv, ';'); fclose($fp); }

                // Email
                $subj = 'Новый заказ GrumantHome — ' . number_format($total, 0, ',', ' ') . ' ₽';
                $body = "Заказ с сайта GrumantHome\n\nИмя: $name\nТелефон: $phone\nКомментарий: $comment\n\n";
                foreach ($cart as $item) {
                    $body .= "• " . $item['name'] . "\n  Цвет: " . $item['color'] . ", Размер: " . $item['width'] . "×" . $item['height'] . " см";
                    if (!empty($item['grommet'])) $body .= ", Люверсы: " . $item['grommet'];
                    $body .= ", Кол-во: " . $item['qty'] . " шт., Цена: " . number_format($item['price'], 0, ',', ' ') . " ₽\n\n";
                }
                $body .= "ИТОГО: " . number_format($total, 0, ',', ' ') . " ₽";
                @mail($ORDER_EMAIL, $subj, $body, "From: orders@grumanthome.ru\r\nContent-Type: text/plain; charset=utf-8");

                $msg = 'Заказ оформлен! Мы свяжемся с вами.';
                $msgType = 'ok';
            }
        }
    }
}

// Админка: сохранение остатков
if (isset($_POST['save_stock']) && ($_POST['pass'] ?? '') === $ADMIN_PASS) {
    $new = [];
    if (isset($_POST['stock']) && is_array($_POST['stock'])) {
        foreach ($_POST['stock'] as $k => $v) {
            $v = (int)$v;
            if ($v < 0) $v = 0;
            if ($v > 999) $v = 999;
            $new[$k] = $v;
        }
    }
    stock_save($new);
    $msg = 'Остатки сохранены';
    $msgType = 'ok';
}

// ─── РОУТИНГ ───
$page = $_GET['p'] ?? 'home';
$pid  = $_GET['id'] ?? '';

// ─── HTML ───
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GrumantHome — готовые шторы и тюль с доставкой</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ivory:#F7F4EF;--sand:#D7C4A5;--bronze:#7A5B3A;--gr:#222;--white:#fff;--ez:cubic-bezier(0.22,1,0.36,1)}
*{box-sizing:border-box}
body{font-family:'Manrope',sans-serif;margin:0;color:var(--gr);background:var(--white);font-size:18px;line-height:30px;overflow-x:hidden}
h1,h2,h3,.serif{font-family:'Cormorant Garamond',serif;font-weight:500;margin:0}
h1{font-size:72px;line-height:78px}h2{font-size:52px;line-height:56px}h3{font-size:36px;line-height:42px}
.wrap{max-width:1440px;margin:0 auto;padding:0 80px}
@media(max-width:768px){.wrap{padding:0 16px}h1{font-size:44px;line-height:50px}h2{font-size:36px;line-height:40px}}

/* Header */
header{height:88px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--ivory);position:sticky;top:0;background:rgba(255,255,255,0.96);backdrop-filter:blur(12px);z-index:50}
.logo{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--gr);text-decoration:none;letter-spacing:1px}
nav{display:flex;align-items:center}
nav a{color:var(--gr);text-decoration:none;margin-left:28px;font-size:18px;transition:color .18s var(--ez)}
nav a:hover{color:var(--bronze)}
.hicons{display:flex;align-items:center;gap:16px}
.cartic{position:relative;font-size:22px;text-decoration:none;cursor:pointer;background:none;border:none;color:var(--gr);padding:4px}
#cartbadge{position:absolute;top:-8px;right:-12px;background:var(--bronze);color:#fff;border-radius:999px;font-size:12px;line-height:18px;min-width:18px;text-align:center;padding:0 6px;display:none;font-family:'Manrope'}
.burger{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:var(--gr);padding:4px 10px}
@media(max-width:768px){header{height:70px}nav{display:none;position:absolute;top:70px;left:0;right:0;background:#fff;flex-direction:column;padding:16px 24px;border-bottom:1px solid var(--ivory);box-shadow:0 12px 30px rgba(0,0,0,.08);z-index:60}nav.open{display:flex}nav a{margin:10px 0;font-size:18px}.burger{display:block}}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;height:56px;padding:0 40px;border-radius:999px;background:var(--bronze);color:#fff;border:none;cursor:pointer;font-family:'Manrope';font-size:18px;text-decoration:none;transition:opacity .18s var(--ez),transform .18s var(--ez)}
.btn:hover{opacity:.88;transform:translateY(-1px)}
.btn.ghost{background:transparent;color:var(--gr);border:1px solid var(--sand)}
.btn.small{height:44px;padding:0 24px;font-size:15px}
.btn:disabled{opacity:.4;cursor:not-allowed;transform:none}

/* Animations */
@keyframes fade{from{opacity:0}}@keyframes rise{from{opacity:0;transform:translateY(24px)}}
.fade{animation:fade .6s var(--ez)}.rise{animation:rise .7s var(--ez)}

/* Hero */
.hero{height:920px;background:linear-gradient(120deg,var(--ivory) 0%,#efe7da 55%,var(--sand) 100%);display:flex;align-items:flex-end;padding:0 0 140px 120px}
.hero .in{max-width:640px}
.hero p{font-size:20px;line-height:30px;max-width:420px;color:#5c5347;margin-top:20px}
.hero .row{margin-top:36px;display:flex;gap:16px}
@media(max-width:768px){.hero{height:780px;padding:0 16px 80px}.hero p{font-size:18px}.hero .row{flex-direction:column}.hero .btn{width:100%}}

/* Sections */
section{padding:160px 0 0}
@media(max-width:768px){section{padding-top:80px}}

/* Cards */
.cards{display:flex;gap:24px;flex-wrap:wrap}
.card{flex:1 1 320px;max-width:405px;text-decoration:none;color:var(--gr);display:block;cursor:pointer}
.card .imgbox{width:100%;height:500px;border-radius:20px;overflow:hidden;background:var(--ivory);position:relative}
.card .imgbox img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s var(--ez)}
.card:hover .imgbox img{transform:scale(1.04)}
.card .ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#b5a89a;font-size:14px;text-transform:uppercase;letter-spacing:.1em}
.card .nm{font-family:'Cormorant Garamond',serif;font-size:24px;margin-top:18px}
.card .ds{font-size:16px;line-height:26px;color:#6b6156}
.card .pr{font-size:20px;font-weight:600;margin-top:6px;color:var(--bronze)}
@media(max-width:768px){.card{flex:1 1 100%;max-width:100%}.card .imgbox{height:380px}}

/* Why */
.why{background:var(--ivory);margin-top:160px;padding:120px 0}
.wcard{flex:1 1 300px;max-width:384px;background:#fff;border-radius:20px;padding:40px}
.wcard .ic{width:40px;height:40px;border-radius:50%;background:var(--sand);margin-bottom:24px}
.wcard h3{font-size:24px;line-height:30px;margin-bottom:12px}
.wcard p{font-size:18px;line-height:28px;color:#6b6156;margin:0}
@media(max-width:768px){.why{margin-top:80px;padding:80px 0}.wcard{flex:1 1 100%;max-width:100%}}

/* Best */
.best .cards{gap:40px}
.bcard{flex:1 1 460px;max-width:620px}
.bcard .imgbox{height:430px}
@media(max-width:768px){.bcard{flex:1 1 100%;max-width:100%}.bcard .imgbox{height:320px}}

/* Macro */
.macro{height:620px;margin-top:160px;background:linear-gradient(160deg,var(--sand),var(--bronze));display:flex;align-items:center;justify-content:center;border-radius:20px}
.macro span{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:36px;color:#fff;text-align:center;padding:0 40px}
@media(max-width:768px){.macro{margin-top:80px;height:420px}.macro span{font-size:28px}}

/* Product page */
.pd-wrap{display:flex;gap:60px;margin-top:60px}
.pd-img{flex:0 0 45%;max-width:540px}
.pd-img .imgbox{height:720px;border-radius:20px;overflow:hidden;background:var(--ivory)}
.pd-info{flex:1}
.pd-title{font-family:'Cormorant Garamond',serif;font-size:48px;line-height:52px;margin-bottom:16px}
.pd-price{font-size:36px;font-weight:600;margin-bottom:8px}
.pd-range{font-size:20px;color:#6b6156;margin-bottom:24px}
.pd-hint{font-size:16px;color:#8a8078;margin-bottom:32px}
.opt-group{margin-bottom:28px}
.opt-label{font-size:14px;color:#8a8078;margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em}
.opt-row{display:flex;gap:10px;flex-wrap:wrap}
.opt-btn{padding:10px 20px;border:1px solid var(--sand);border-radius:12px;background:#fff;color:var(--gr);font-family:'Manrope';font-size:15px;cursor:pointer;transition:all .18s var(--ez)}
.opt-btn:hover{border-color:var(--bronze)}
.opt-btn.active{background:var(--bronze);color:#fff;border-color:var(--bronze)}
.opt-btn.disabled{background:#f0f0f0;color:#bbb;border-color:#e0e0e0;cursor:not-allowed}
.eyelet-btn{width:56px;height:56px;border-radius:50%;border:2px solid var(--sand);background:#fff;cursor:pointer;transition:all .18s var(--ez);display:flex;align-items:center;justify-content:center;font-size:12px;padding:4px;text-align:center;line-height:1.2}
.eyelet-btn:hover{border-color:var(--bronze)}
.eyelet-btn.active{border-color:var(--bronze);box-shadow:0 0 0 3px rgba(122,91,58,.2)}
.qty-row{display:flex;align-items:center;gap:16px;margin:32px 0}
.qty-row button{width:44px;height:44px;border:1px solid var(--sand);border-radius:50%;background:#fff;font-size:20px;cursor:pointer;transition:all .18s var(--ez)}
.qty-row button:hover{border-color:var(--bronze)}
.qty-row span{font-size:20px;font-weight:600;min-width:30px;text-align:center}
#stocknote{font-size:14px;color:#8a8078;margin-top:8px;min-height:20px}
.pd-desc{margin-top:40px;padding-top:40px;border-top:1px solid var(--ivory);font-size:16px;color:#6b6156;line-height:1.7}
@media(max-width:768px){.pd-wrap{flex-direction:column}.pd-img{max-width:100%}.pd-img .imgbox{height:480px}.pd-title{font-size:36px}}

/* Cart */
.cart-page{margin-top:60px}
.cart-item{display:flex;gap:24px;padding:28px 0;border-bottom:1px solid var(--ivory);align-items:flex-start}
.cart-item-img{width:100px;height:130px;border-radius:16px;background:var(--ivory);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px;color:#b5a89a;text-align:center;padding:8px}
.cart-item-info{flex:1}
.cart-item-name{font-size:20px;font-weight:500;margin-bottom:6px}
.cart-item-meta{font-size:15px;color:#6b6156;margin-bottom:8px;line-height:1.5}
.cart-item-price{font-size:20px;font-weight:600;color:var(--bronze)}
.cart-item-qty{display:flex;align-items:center;gap:12px;margin-top:12px}
.cart-item-qty button{width:36px;height:36px;border:1px solid var(--sand);border-radius:50%;background:#fff;font-size:18px;cursor:pointer}
.cart-item-qty span{font-size:16px;font-weight:500;min-width:24px;text-align:center}
.cart-item-remove{margin-left:auto;background:none;border:none;color:#8a8078;font-size:14px;cursor:pointer;text-decoration:underline;text-underline-offset:3px}
.cart-item-remove:hover{color:var(--bronze)}
.cart-total{font-size:32px;font-weight:600;text-align:right;margin:40px 0}
.cart-limit{color:#8a8078;font-size:15px;margin-bottom:24px}
.cart-form{margin-top:40px;max-width:600px}
.cart-form .field{margin-bottom:24px}
.cart-form label{display:block;font-size:14px;color:#8a8078;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em}
.cart-form input,.cart-form textarea{width:100%;padding:14px 0;border:none;border-bottom:1px solid var(--sand);background:transparent;font-family:'Manrope';font-size:16px;outline:none;transition:border-color .18s var(--ez)}
.cart-form input:focus,.cart-form textarea:focus{border-bottom-color:var(--bronze)}
.cart-form textarea{resize:vertical;min-height:80px}

/* Toast */
#toast{position:fixed;left:50%;bottom:32px;transform:translateX(-50%) translateY(20px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.18);padding:20px 28px;display:none;align-items:center;gap:20px;z-index:300;max-width:calc(100% - 32px);flex-wrap:wrap;justify-content:center;transition:all .4s var(--ez)}
#toast.on{display:flex;transform:translateX(-50%) translateY(0)}

/* Feedback */
.fbbtn{position:fixed;right:24px;bottom:24px;z-index:50;height:52px;padding:0 22px;border-radius:999px;background:var(--bronze);color:#fff;border:none;cursor:pointer;font-family:'Manrope';font-size:16px;box-shadow:0 8px 24px rgba(0,0,0,.15);transition:all .18s var(--ez)}
.fbbtn:hover{transform:translateY(-2px)}
.fbpanel{position:fixed;right:24px;bottom:88px;z-index:50;width:340px;background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.18);padding:24px;display:none}
.fbpanel.on{display:block}
.fbpanel textarea{height:120px;width:100%;padding:12px;border:1px solid var(--sand);border-radius:12px;font-family:'Manrope';font-size:15px;resize:vertical;margin:8px 0}
.fbpanel input{width:100%;padding:12px;border:1px solid var(--sand);border-radius:12px;font-family:'Manrope';font-size:15px;margin:8px 0}
.fbpanel b{display:block;margin-bottom:8px;font-size:14px}
@media(max-width:768px){.fbpanel{right:16px;width:calc(100% - 32px)}}

/* Admin */
.admin-wrap{margin-top:60px}
.admin-wrap h2{font-family:'Cormorant Garamond',serif;font-size:42px;margin-bottom:40px}
.admin-table{width:100%;border-collapse:collapse;margin-bottom:40px}
.admin-table th{text-align:left;padding:12px 16px;border-bottom:2px solid var(--sand);font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#8a8078}
.admin-table td{padding:12px 16px;border-bottom:1px solid var(--ivory);font-size:16px}
.admin-table td input{width:80px;padding:8px;border:1px solid var(--sand);border-radius:8px;font-family:'Manrope';font-size:15px;text-align:center}
.admin-login{max-width:400px;margin:100px auto;padding:40px;background:var(--ivory);border-radius:20px}
.admin-login h2{margin-bottom:24px}
.admin-login input{width:100%;padding:14px;border:1px solid var(--sand);border-radius:12px;font-family:'Manrope';font-size:16px;margin-bottom:16px}

/* Footer */
footer{margin-top:160px;padding:60px 0;border-top:1px solid var(--ivory);color:#6b6156;font-size:16px;text-align:center}
footer a{color:#6b6156;text-decoration:none;transition:color .18s var(--ez)}
footer a:hover{color:var(--bronze)}
@media(max-width:768px){footer{margin-top:80px}}

/* Msg */
.msgbox{padding:16px 24px;border-radius:12px;margin-bottom:24px;font-size:15px}
.msgbox.ok{background:#d1fae5;color:#065f46}
.msgbox.err{background:#fee2e2;color:#991b1b}

/* Misc */
.backlink{display:inline-flex;align-items:center;gap:8px;color:#8a8078;text-decoration:none;margin-bottom:24px;transition:color .18s}
.backlink:hover{color:var(--gr)}
.empty{text-align:center;padding:80px 0;color:#8a8078}
</style>
</head>
<body class="fade">

<header>
  <div class="wrap" style="display:flex;align-items:center;justify-content:space-between;width:100%">
    <a class="logo" href="?p=home">GrumantHome</a>
    <nav id="nav">
      <a href="?p=catalog">Каталог</a>
      <a href="?p=about">О Grumant</a>
      <a href="?p=delivery">Доставка</a>
      <a href="?p=contacts">Контакты</a>
    </nav>
    <div class="hicons">
      <a class="cartic" href="?p=cart" aria-label="Корзина">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span id="cartbadge">0</span>
      </a>
      <button class="burger" id="burger" aria-label="Меню">☰</button>
    </div>
  </div>
</header>

<?php if ($msg): ?>
<div class="wrap" style="padding-top:24px">
  <div class="msgbox <?=htmlspecialchars($msgType)?>"><?=htmlspecialchars($msg)?></div>
</div>
<?php endif; ?>

<?php
// ═══════════════════════════════════════════════════
// СТРАНИЦЫ
// ═══════════════════════════════════════════════════

// ─── HOME ───
if ($page === 'home'):
?>
<div class="hero rise">
  <div class="in">
    <h1>Текстиль, который дышит светом</h1>
    <p>Готовые шторы и тюль. Выбираете ткань, размер и цвет — мы отправляем в день заказа.</p>
    <div class="row">
      <a class="btn" href="?p=catalog">Смотреть каталог</a>
      <a class="btn ghost" href="?p=about">О Grumant</a>
    </div>
  </div>
</div>

<section>
  <div class="wrap">
    <h2>Коллекции</h2>
    <div class="cards" style="margin-top:60px">
      <a class="card" href="?p=catalog&cat=lenta">
        <div class="imgbox"><div class="ph">Тюль на шторной ленте</div></div>
        <div class="nm">Тюль на шторной ленте</div>
        <div class="ds">Мягкая драпировка без утяжеления</div>
      </a>
      <a class="card" href="?p=catalog&cat=luversy">
        <div class="imgbox"><div class="ph">Тюль на люверсах</div></div>
        <div class="nm">Тюль на люверсах</div>
        <div class="ds">Ровная волна складок на кольцах</div>
      </a>
      <a class="card" href="?p=catalog&cat=shtory">
        <div class="imgbox"><div class="ph">Шторы</div></div>
        <div class="nm">Шторы</div>
        <div class="ds">Плотные портьеры для затемнения</div>
      </a>
    </div>
  </div>
</section>

<div class="why">
  <div class="wrap">
    <h2>Почему GrumantHome</h2>
    <div class="cards" style="margin-top:60px">
      <div class="wcard"><div class="ic"></div><h3>Готовый ассортимент</h3><p>Выбираете ткань, размер и цвет — мы отправляем в день заказа.</p></div>
      <div class="wcard"><div class="ic"></div><h3>Натуральные ткани</h3><p>Лён, хлопок, вуаль. Дышат, пропускают свет, создают уют.</p></div>
      <div class="wcard"><div class="ic"></div><h3>Быстрая доставка</h3><p>По Москве — курьером, по России — Ozon или СДЭК. Включена в цену.</p></div>
    </div>
  </div>
</div>

<section class="best">
  <div class="wrap">
    <h2>Популярные модели</h2>
    <div class="cards" style="margin-top:60px">
      <?php $best = ['len-lenta','vetochki','blackout-luv']; foreach ($best as $bid): $bp = $PRODUCTS[$bid]; ?>
      <a class="card bcard" href="?p=product&id=<?=$bid?>">
        <div class="imgbox"><div class="ph"><?=$bp['name']?></div></div>
        <div class="nm"><?=$bp['name']?></div>
        <div class="pr">от <?=number_format($bp['widths'][0]['price'],0,',',' ')?> ₽</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="wrap">
  <div class="macro"><span>Фактура определяет характер света</span></div>
</div>

<?php
// ─── CATALOG ───
elseif ($page === 'catalog'):
  $cat = $_GET['cat'] ?? '';
  $cat_names = ['lenta'=>'Тюль на шторной ленте','luversy'=>'Тюль на люверсах','shtory'=>'Шторы'];
  $title = $cat_names[$cat] ?? 'Каталог';
?>
<section>
  <div class="wrap">
    <a class="backlink" href="?p=home">← на главную</a>
    <h2 style="margin-top:20px"><?=htmlspecialchars($title)?></h2>
    <div class="cards" style="margin-top:60px">
      <?php foreach ($PRODUCTS as $pid => $p):
        if ($cat && !str_contains($p['cat'], $cat)) continue;
        $price_from = $p['widths'][0]['price'];
      ?>
      <a class="card" href="?p=product&id=<?=$pid?>">
        <div class="imgbox"><div class="ph"><?=$p['name']?></div></div>
        <div class="nm"><?=$p['name']?></div>
        <div class="ds"><?=$p['desc']?></div>
        <div class="pr">от <?=number_format($price_from,0,',',' ')?> ₽</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
// ─── PRODUCT ───
elseif ($page === 'product' && isset($PRODUCTS[$pid])):
  $p = $PRODUCTS[$pid];
  $price_range = count($p['widths']) === 1
    ? number_format($p['widths'][0]['price'],0,',',' ').' ₽'
    : number_format($p['widths'][0]['price'],0,',',' ').' ₽ – '.number_format(end($p['widths'])['price'],0,',',' ').' ₽';
  $stock_data = [];
  foreach ($p['colors'] as $c) $stock_data[$c] = stock_get($pid, $c);
?>
<section>
  <div class="wrap">
    <a class="backlink" href="?p=catalog&cat=<?=$p['cat']?>">← назад</a>
    <div class="pd-wrap">
      <div class="pd-img">
        <div class="imgbox"><div class="ph"><?=$p['name']?></div></div>
      </div>
      <div class="pd-info">
        <h1 class="pd-title"><?=$p['name']?></h1>
        <div class="pd-price" id="pdPrice"><?=$price_range?></div>
        <div class="pd-hint">Выберите цвет и размеры (высоту, ширину):</div>

        <div class="opt-group">
          <div class="opt-label">Высота, см</div>
          <div class="opt-row" id="hOpts">
            <?php foreach ($p['heights'] as $h): ?>
            <button class="opt-btn" data-h="<?=$h?>" onclick="setH(<?=$h?>)"><?=$h?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="opt-group">
          <div class="opt-label">Ширина, см</div>
          <div class="opt-row" id="wOpts">
            <?php foreach ($p['widths'] as $w): ?>
            <button class="opt-btn" data-w="<?=$w['val']?>" data-price="<?=$w['price']?>" onclick="setW(<?=$w['val']?>,<?=$w['price']?>)"><?=$w['label']?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="opt-group">
          <div class="opt-label">Цвет ткани</div>
          <div class="opt-row" id="cOpts">
            <?php foreach ($p['colors'] as $c):
              $stk = stock_get($pid, $c);
              $dis = ($stk === 0) ? 'disabled' : '';
            ?>
            <button class="opt-btn <?=$dis?>" data-c="<?=$c?>" onclick="setC('<?=$c?>')" <?=$dis?'disabled':''?>><?=$c?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($p['grommets']): ?>
        <div class="opt-group" id="gBlock">
          <div class="opt-label">Цвет люверсов</div>
          <div class="opt-row" id="gOpts">
            <?php foreach ($p['grommets'] as $g): ?>
            <button class="eyelet-btn" data-g="<?=$g?>" onclick="setG('<?=$g?>')"><?=$g?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="qty-row">
          <button onclick="changeQ(-1)">−</button>
          <span id="qtyVal">1</span>
          <button onclick="changeQ(1)">+</button>
        </div>
        <div id="stocknote"></div>

        <button class="btn" id="addBtn" style="width:100%;max-width:320px" onclick="addToCart()">Заказать</button>

        <div class="pd-desc">
          <p><?=$p['desc']?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const P = {
  id:'<?=$pid?>', name:'<?=addslashes($p['name'])?>',
  heights:<?=json_encode($p['heights'])?>,
  widths:<?=json_encode($p['widths'])?>,
  colors:<?=json_encode($p['colors'])?>,
  grommets:<?=json_encode($p['grommets'])?>,
  stock:<?=json_encode($stock_data)?>,
  maxOrder:<?=$MAX_ORDER?>
};
let S = {h:<?=$p['heights'][0]?>, w:<?=$p['widths'][0]['val']?>, price:<?=$p['widths'][0]['price']?>, c:'<?=$p['colors'][0]?>', g:'<?=$p['grommets'] ? $p['grommets'][0] : ''?>', q:1};
let MAXQ = 999;

function fmt(n){return n.toLocaleString('ru-RU')+' ₽';}
function setH(v){S.h=v; render();}
function setW(v,p){S.w=v; S.price=p; render();}
function setC(v){S.c=v; render();}
function setG(v){S.g=v; render();}
function changeQ(d){S.q+=d; if(S.q<1)S.q=1; if(MAXQ>0 && MAXQ<999 && S.q>MAXQ)S.q=MAXQ; renderQty();}

function render(){
  document.querySelectorAll('#hOpts .opt-btn').forEach(b=>b.classList.toggle('active',+b.dataset.h===S.h));
  document.querySelectorAll('#wOpts .opt-btn').forEach(b=>b.classList.toggle('active',+b.dataset.w===S.w));
  document.querySelectorAll('#cOpts .opt-btn').forEach(b=>b.classList.toggle('active',b.dataset.c===S.c));
  if(P.grommets) document.querySelectorAll('#gOpts .eyelet-btn').forEach(b=>b.classList.toggle('active',b.dataset.g===S.g));

  const avail = P.stock[S.c] ?? 999;
  MAXQ = (avail===999)?0:avail;
  const note = document.getElementById('stocknote');
  if(MAXQ>0 && MAXQ<999){note.textContent='В наличии: '+MAXQ+' шт.';}
  else if(MAXQ===0){note.textContent='Нет в наличии';}
  else{note.textContent='';}

  const btn = document.getElementById('addBtn');
  if(MAXQ===0){btn.disabled=true; btn.textContent='Нет в наличии';}
  else{btn.disabled=false; btn.textContent='Заказать';}

  document.getElementById('pdPrice').textContent = fmt(S.price);
  renderQty();
}
function renderQty(){document.getElementById('qtyVal').textContent=S.q;}

function addToCart(){
  if(MAXQ===0) return;
  let cart = JSON.parse(localStorage.getItem('gh_cart')||'[]');
  const totalQty = cart.reduce((s,it)=>s+it.q,0);
  if(totalQty + S.q > P.maxOrder){alert('В одном заказе не более '+P.maxOrder+' штор. Оформите второй заказ или напишите нам — обсудим лично.'); return;}

  const item = {id:P.id, name:P.name, h:S.h, w:S.w, price:S.price, color:S.c, grommet:S.g, q:S.q};
  const exist = cart.find(x=>x.id===item.id && x.h===item.h && x.w===item.w && x.color===item.color && x.grommet===item.grommet);
  if(exist){exist.q += item.q; if(MAXQ>0 && MAXQ<999 && exist.q>MAXQ) exist.q=MAXQ;}
  else{cart.push(item);}

  localStorage.setItem('gh_cart', JSON.stringify(cart));
  showToast(P.name+' — в корзине. Всего: '+cart.reduce((s,it)=>s+it.q,0)+' шт.');
  updateBadge();
}
function showToast(text){
  const t=document.getElementById('toast');
  t.innerHTML='<span style="font-size:15px">'+text+'</span><a class="btn small" href="?p=cart">В корзину</a><button class="btn small ghost" onclick="hideToast()">Продолжить</button>';
  t.classList.add('on'); setTimeout(hideToast,6000);
}
function hideToast(){document.getElementById('toast').classList.remove('on');}
function updateBadge(){
  const cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  const n=cart.reduce((s,it)=>s+it.q,0);
  const b=document.getElementById('cartbadge');
  b.textContent=n; b.style.display=n?'flex':'none';
}
render(); updateBadge();
</script>

<?php
// ─── CART ───
elseif ($page === 'cart'):
?>
<section class="cart-page">
  <div class="wrap">
    <h2>Корзина</h2>
    <div id="cartList"></div>
    <div class="cart-total" id="cartTotal">0 ₽</div>
    <div class="cart-limit">В одном заказе не более <?=$MAX_ORDER?> штор. Нужно больше? Оформите второй заказ или <a href="?p=contacts">напишите нам</a> — обсудим лично.</div>

    <form method="post" class="cart-form" onsubmit="return prepareCart()">
      <input type="hidden" name="cart_data" id="cartData">
      <div class="field">
        <label>Ваше имя *</label>
        <input type="text" name="c_name" required placeholder="Анна">
      </div>
      <div class="field">
        <label>Телефон *</label>
        <input type="tel" name="c_phone" required placeholder="+7 (999) 123-45-67">
      </div>
      <div class="field">
        <label>Комментарий</label>
        <textarea name="c_comment" placeholder="Адрес, удобное время доставки..."></textarea>
      </div>
      <button type="submit" name="place_order" class="btn" style="width:100%;max-width:320px">Отправить заказ</button>
    </form>
  </div>
</section>

<script>
const MAX_ORDER = <?=$MAX_ORDER?>;
function renderCart(){
  let cart = JSON.parse(localStorage.getItem('gh_cart')||'[]');
  const list = document.getElementById('cartList');
  const totalEl = document.getElementById('cartTotal');
  if(cart.length===0){list.innerHTML='<div class="empty">Корзина пуста</div>';totalEl.textContent='0 ₽';return;}

  let html=''; let total=0;
  cart.forEach((it,i)=>{
    const sum=it.price*it.q; total+=sum;
    html+='<div class="cart-item">';
    html+='<div class="cart-item-img">'+it.name+'</div>';
    html+='<div class="cart-item-info">';
    html+='<div class="cart-item-name">'+it.name+'</div>';
    html+='<div class="cart-item-meta">Высота: '+it.h+' см, Ширина: '+it.w+' см, Цвет: '+it.color;
    if(it.grommet) html+=', Люверсы: '+it.grommet;
    html+='</div>';
    html+='<div class="cart-item-price">'+sum.toLocaleString('ru-RU')+' ₽</div>';
    html+='<div class="cart-item-qty"><button onclick="modQ('+i+',-1)">−</button><span>'+it.q+'</span><button onclick="modQ('+i+',1)">+</button></div>';
    html+='</div>';
    html+='<button class="cart-item-remove" onclick="delItem('+i+')">Удалить</button>';
    html+='</div>';
  });
  html+='<div style="margin-top:20px"><button class="btn ghost small" onclick="clearCart()">Очистить корзину</button></div>';
  list.innerHTML=html;
  totalEl.textContent=total.toLocaleString('ru-RU')+' ₽';
  updateBadge();
}
function modQ(i,d){
  let cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  cart[i].q+=d; if(cart[i].q<1)cart[i].q=1;
  const total=cart.reduce((s,it)=>s+it.q,0);
  if(total>MAX_ORDER){alert('Максимум '+MAX_ORDER+' штор в заказе'); cart[i].q-=d; return;}
  localStorage.setItem('gh_cart',JSON.stringify(cart)); renderCart();
}
function delItem(i){
  let cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  cart.splice(i,1); localStorage.setItem('gh_cart',JSON.stringify(cart)); renderCart();
}
function clearCart(){
  if(confirm('Очистить корзину?')){localStorage.removeItem('gh_cart'); renderCart();}
}
function prepareCart(){
  let cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  if(cart.length===0){alert('Корзина пуста'); return false;}
  document.getElementById('cartData').value=JSON.stringify(cart);
  localStorage.removeItem('gh_cart');
  return true;
}
function updateBadge(){
  const cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  const n=cart.reduce((s,it)=>s+it.q,0);
  const b=document.getElementById('cartbadge');
  b.textContent=n; b.style.display=n?'flex':'none';
}
renderCart();
</script>

<?php
// ─── ADMIN ───
elseif ($page === 'admin'):
  $pass = $_POST['pass'] ?? $_GET['pass'] ?? '';
  if ($pass !== $ADMIN_PASS):
?>
<div class="wrap">
  <div class="admin-login">
    <h2>Админка</h2>
    <form method="post">
      <input type="password" name="pass" placeholder="Пароль" required>
      <button type="submit" class="btn" style="width:100%">Войти</button>
    </form>
  </div>
</div>
<?php else: $stock = stock_load(); ?>
<div class="wrap admin-wrap">
  <h2>Управление остатками</h2>
  <p style="color:#8a8078;margin-bottom:24px">999 = безлимит. 0 = нет в наличии. Любое другое число — ограничение по количеству.</p>
  <form method="post">
    <input type="hidden" name="pass" value="<?=htmlspecialchars($pass)?>">
    <table class="admin-table">
      <tr><th>Товар</th><th>Цвет ткани</th><th>Текущий остаток</th><th>Новое значение</th></tr>
      <?php foreach ($PRODUCTS as $k => $p): foreach ($p['colors'] as $c):
        $key = $k . '|' . $c;
        $val = isset($stock[$key]) ? (int)$stock[$key] : 999;
      ?>
      <tr>
        <td><?=htmlspecialchars($p['name'])?></td>
        <td><?=htmlspecialchars($c)?></td>
        <td><b><?=$val===999?'∞ (безлимит)':$val?></b></td>
        <td><input type="number" name="stock[<?=htmlspecialchars($key)?>]" value="<?=$val?>" min="0" max="999"></td>
      </tr>
      <?php endforeach; endforeach; ?>
    </table>
    <button type="submit" name="save_stock" class="btn">Сохранить</button>
  </form>
</div>
<?php endif; ?>

<?php
// ─── DELIVERY ───
elseif ($page === 'delivery'):
?>
<section>
  <div class="wrap">
    <a class="backlink" href="?p=home">← на главную</a>
    <h2 style="margin-top:20px">Доставка</h2>
    <p style="max-width:640px;color:#6b6156;margin-top:24px;line-height:1.7">
      Доставка включена в стоимость штор. Отправляем курьером по Москве и Ozon / СДЭК по России.<br><br>
      Срок — 1–3 рабочих дня. При получении можно осмотреть товар.<br><br>
      Если шторы не подошли — вернём деньги за товар. Стоимость доставки не возвращается.
    </p>
  </div>
</section>

<?php
// ─── CONTACTS ───
elseif ($page === 'contacts'):
?>
<section>
  <div class="wrap">
    <a class="backlink" href="?p=home">← на главную</a>
    <h2 style="margin-top:20px">Контакты</h2>
    <p style="max-width:640px;color:#6b6156;margin-top:24px;line-height:1.7">
      Email: <a href="mailto:<?=$ORDER_EMAIL?>"><?=$ORDER_EMAIL?></a><br>
      Телефон: <a href="tel:+79991234567">+7 (999) 123-45-67</a><br><br>
      Пишите, звоните, оставляйте заметки через кнопку внизу экрана — отвечаем в течение дня.
    </p>
  </div>
</section>

<?php
// ─── ABOUT ───
elseif ($page === 'about'):
?>
<section>
  <div class="wrap">
    <a class="backlink" href="?p=home">← на главную</a>
    <h2 style="margin-top:20px">О Grumant</h2>
    <p style="max-width:640px;color:#6b6156;margin-top:24px;line-height:1.7">
      GrumantHome — готовые шторы и тюль из натуральных тканей. В ассортименте — лён, хлопок, вуаль, blackout.<br><br>
      Все шторы в наличии, отправляем в день заказа. Доставка по России включена в цену.
    </p>
  </div>
</section>

<?php
// ─── 404 ───
else:
?>
<section>
  <div class="wrap">
    <div class="empty">
      <h2>Страница не найдена</h2>
      <p><a class="backlink" href="?p=home">← на главную</a></p>
    </div>
  </div>
</section>
<?php endif; ?>

<footer>
  <div class="wrap">
    GrumantHome — готовые шторы и тюль. Доставка по России.<br>
    <a href="tel:+79991234567">+7 (999) 123-45-67</a> · 
    <a href="mailto:<?=$ORDER_EMAIL?>"><?=$ORDER_EMAIL?></a>
  </div>
</footer>

<div id="toast"></div>

<button class="fbbtn" id="fbbtn">✎ Заметка</button>
<div class="fbpanel" id="fbpanel">
  <form method="post" onsubmit="return fbSend()">
    <b>Что зацепило глаз или не сработало?</b>
    <textarea name="fb_text" id="fbText" placeholder="Ваш отзыв..." required></textarea>
    <b>Как к вам обращаться</b>
    <input type="text" name="fb_name" id="fbName" placeholder="Имя">
    <input type="hidden" name="fb_page" id="fbPage" value="">
    <button type="submit" name="fb_send" class="btn" style="width:100%;height:48px;margin-top:8px">Отправить</button>
  </form>
</div>

<script>
document.getElementById('burger').addEventListener('click',()=>document.getElementById('nav').classList.toggle('open'));
document.getElementById('fbbtn').addEventListener('click',()=>{
  document.getElementById('fbpanel').classList.toggle('on');
  document.getElementById('fbPage').value=location.href;
});
function fbSend(){document.getElementById('fbpanel').classList.remove('on'); return true;}
(function(){
  const cart=JSON.parse(localStorage.getItem('gh_cart')||'[]');
  const n=cart.reduce((s,it)=>s+it.q,0);
  const b=document.getElementById('cartbadge');
  b.textContent=n; b.style.display=n?'flex':'none';
})();
</script>

</body>
</html>
