<?php
/**
 * @var Template $template
 * @var Resources $resources
 * @var Notification[] $notifications
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/images/favicons/<?php echo DOMAIN ?>.ico">

    <title><?php echo $template->v('title') . ' | ' . TITLE ?></title>

    <!-- Bootstrap core CSS -->
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="/resources/css_single/?f=bootstrap-reset" rel="stylesheet" />

    <!--external css-->
    <link href="http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet" />
    <link href="//cdn.datatables.net/plug-ins/be7019ee387/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet" />
    <link href="//cdn.jsdelivr.net/jquery.gritter/1.7.4/css/jquery.gritter.css" rel="stylesheet" />

    <?php echo $resources->get_css_urls(); ?>

    <!-- Custom styles for this template -->
    <link type="text/css" rel="stylesheet" href="/resources/css_single/?f=style" />
    <link type="text/css" rel="stylesheet" href="/resources/css_single/?f=style-responsive" />

    <link type="text/css" rel="stylesheet" href="/resources/css/?f=<?php echo $resources->get_css_file(); ?>" />

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- jQuery -->
    <script src="//code.jquery.com/jquery-2.1.1.js"></script>
</head>
<body>

<section id="container" >

<?php if ( !empty( $notifications ) ): ?>
    <?php foreach( $notifications as $notification ): ?>
        <div class="alert alert-dismissible alert-<?php echo $notification->success ? 'success' : 'danger' ?> fade in" role="alert">
            <button data-dismiss="alert" class="close close-sm" type="button">
                <i class="fa fa-times"></i>
            </button>
            <?php echo $notification->message ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!--header start-->
<header class="header white-bg">
<div class="sidebar-toggle-box">
    <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
</div>
<!--logo start-->
<a href="/" class="logo"><img src="/images/logos/<?php echo DOMAIN; ?>.png" width="<?php echo LOGO_WIDTH; ?>" alt="<?php echo TITLE, ' ', _('Logo'); ?>" /></a>
<!--logo end-->
<div class="top-nav ">
    <!--search & user info start-->
    <ul class="nav pull-right top-menu">
        <!-- kb support dropdown start -->
        <li class="dropdown" id="kb-dropdown">
            <a data-toggle="dropdown" class="dropdown-toggle" href="javascript:;">
                <span class="glyphicon glyphicon-question-sign"></span>
            </a>
            <ul class="dropdown-menu extended">
                <div class="log-arrow-up"></div>
                <li><a href="#" data-toggle="modal" data-target="#support-modal">Support Request</a></li>
                <?php
                    if ( !empty( $kbh_articles ) )
                        foreach ( $kbh_articles as $kbh_article ):
                ?>
                    <li>
                        <a href="<?php echo url::add_query_arg( 'aid', $kbh_article->id, '/kb/article/' ); ?>" title="<?php echo $kbh_article->title; ?>" target="_blank"><?php echo $kbh_article->title; ?></a>
                    </li>
                <?php endforeach ; ?>
                <li><a href="/kb/browser/">Browser Support</a></li>
            </ul>
        </li>
        <!-- kb support dropdown end -->
        <!-- user login dropdown start-->
        <li class="dropdown">
            <a data-toggle="dropdown" class="dropdown-toggle" href="javascript:;">
                <span class="account-name"><?php echo $user->account->title ?></span> | <span class="username"><?php echo $user->contact_name ?></span>
                <b class="caret"></b>
            </a>
            <ul class="dropdown-menu extended logout">
                <div class="log-arrow-up"></div>
                <li><a href="/settings/"><i class="fa fa-suitcase"></i> Settings</a></li>
                <li><a href="/settings/authorized-users/"><i class="fa fa-users"></i> Sub-users</a></li>
                <li><a href="/settings/logo-and-phone/"><i class="fa fa-phone"></i> Logo &amp; Phone</a></li>
                <?php if ( $online_specialist->id ): ?>
                    <li class="big">
                        <?php
                            echo '<a href="mailto:' . $online_specialist->email . '"><i class="fa fa-life-ring"></i> Online Specialist: ' . $online_specialist->contact_name . '</a>';
                            echo '<a href="mailto:' . $online_specialist->email . '">' . $online_specialist->email . '</a>';
                            if ( $online_specialist->work_phone ) echo ' | <a href="tel:' . $online_specialist->work_phone . '">' . $online_specialist->work_phone . '</a>';
                        ?>
                    </li>
                <?php endif; ?>
                <li class="big"><a href="/logout/"><i class="fa fa-key"></i> Log Out</a></li>
            </ul>
        </li>
        <!-- user login dropdown end -->
    </ul>
    <!--search & user info end-->
</div>
</header>
<!--header end-->
<!--sidebar start-->
<aside>
    <div id="sidebar"  class="nav-collapse ">
        <!-- sidebar menu start-->
        <ul class="sidebar-menu" id="nav-accordion">

            <?php if ( $this->account->pages || $user->has_permission( User::ROLE_STORE_OWNER ) ): ?>
                <li class="sub-menu">
                    <a href="javascript:;" <?php if ( $template->v('website') ) echo 'class="active"'?>>
                        <i class="fa fa-globe"></i>
                        <span>Website</span>
                    </a>
                    <ul class="sub">
                        <li <?php if ( $template->v('website/index') ) echo 'class="active"'?>><a href="/website/">Pages</a></li>
                        <li <?php if ( $template->v('website/categories') ) echo 'class="active"'?>><a href="/website/categories/">Categories</a></li>
                        <li <?php if ( $template->v('website/brands') ) echo 'class="active"'?>><a href="/website/brands/">Brands</a></li>
                        <li <?php if ( $template->v('website/sidebar') ) echo 'class="active"'?>><a href="/website/sidebar/">Sidebar</a></li>
                        <li <?php if ( $template->v('website/banners') ) echo 'class="active"'?>><a href="/website/banners/">Banners</a></li>
                        <li <?php if ( $template->v('website/settings') ) echo 'class="active"'?>><a href="/website/settings/">Settings</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ( $this->account->product_catalog || $user->has_permission( User::ROLE_STORE_OWNER ) ): ?>
                <li class="sub-menu">
                    <a href="javascript:;" <?php if ( $template->v('products') ) echo 'class="active"'?>>
                        <i class="fa fa-cube"></i>
                        <span>Products</span>
                    </a>
                    <ul class="sub">
                        <li <?php if ( $template->v('products/index') ) echo 'class="active"'?>><a href="/products/">List Products</a></li>
                        <li <?php if ( $template->v('products/add') ) echo 'class="active"'?>><a href="/products/add/">Add Product</a></li>
                        <li <?php if ( $template->v('products/all') ) echo 'class="active"'?>><a href="/products/all/">All Products</a></li>
                        <li <?php if ( $template->v('products/catalog-dump') ) echo 'class="active"'?>><a href="/products/catalog-dump/">Catalog Dump</a></li>
                        <li <?php if ( $template->v('products/add-bulk') ) echo 'class="active"'?>><a href="/products/add-bulk/">Add Bulk</a></li>
                        <li <?php if ( $template->v('products/block-products') ) echo 'class="active"'?>><a href="/products/block-products/">Block Products</a></li>
                        <li <?php if ( $template->v('products/hide-categories') ) echo 'class="active"'?>><a href="/products/hide-categories/">Hide Categories</a></li>
                        <li <?php if ( $template->v('products/manually-priced') ) echo 'class="active"'?>><a href="/products/manually-priced/">Manually Priced</a></li>
                        <li <?php if ( $template->v('products/pricing-tools') ) echo 'class="active"'?>><a href="/products/add-bulk/">Pricing Tools</a></li>
                        <li <?php if ( $template->v('products/export') ) echo 'class="active"'?>><a href="/products/export/">Export</a></li>
                        <li <?php if ( $template->v('products/reaches') ) echo 'class="active"'?>><a href="/products/reaches/">Reaches</a></li>
                        <li <?php if ( $template->v('products/product-builder') ) echo 'class="active"'?>><a href="/products/product-builder/">Product Builder</a></li>
                        <li <?php if ( $template->v('products/brands') ) echo 'class="active"'?>><a href="/products/brands/">Brands</a></li>
                        <li <?php if ( $template->v('products/top-categories') ) echo 'class="active"'?>><a href="/products/top-categories/">Top Categories</a></li>
                        <li <?php if ( $template->v('products/related-products') ) echo 'class="active"'?>><a href="/products/related-products/">Releated Products</a></li>
                        <li <?php if ( $template->v('products/settings') ) echo 'class="active"'?>><a href="/products/settings/">Settings</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ( $this->account->live || $user->has_permission( User::ROLE_STORE_OWNER ) ): ?>
                <li class="sub-menu">
                    <a href="javascript:;" <?php if ( $template->v('analytics') ) echo 'class="active"'?>>
                        <i class="fa fa-bar-chart-o"></i>
                        <span>Analytics</span>
                    </a>
                    <ul class="sub">
                        <li <?php if ( $template->v('analytics/index') ) echo 'class="active"'?>><a href="/analytics/">Analytics</a></li>
                        <li <?php if ( $template->v('analytics/content-overview') ) echo 'class="active"'?>><a href="/analytics/content-overview/">Content Overview</a></li>
                        <li <?php if ( $template->v('analytics/traffic-sources-overview') ) echo 'class="active"'?>><a href="/analytics/traffic-sources-overview/">Traffic Sources Overview</a></li>
                        <li <?php if ( $template->v('analytics/traffic-sources') ) echo 'class="active"'?>><a href="/analytics/traffic-sources/">Sources</a></li>
                        <li <?php if ( $template->v('analytics/keywords') ) echo 'class="active"'?>><a href="/analytics/keywords/">Keywords</a></li>
                        <li <?php if ( $template->v('analytics/email-marketing') ) echo 'class="active"'?>><a href="/analytics/email-marketing/">Email Marketing</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ( $this->account->email_marketing || $user->has_permission( User::ROLE_STORE_OWNER ) ): ?>
                <li class="sub-menu">
                    <a href="javascript:;" <?php if ( $template->v('email_marketing') ) echo 'class="active"'?>>
                        <i class="fa fa-inbox"></i>
                        <span>Email Marketing</span>
                    </a>
                    <ul class="sub">
                        <li <?php if ( $template->v('email_marketing/index') ) echo 'class="active"'?>><a href="/email_marketing/">Dashboard</a></li>
                        <li <?php if ( $template->v('email_marketing/campaigns') ) echo 'class="active"'?>><a href="/email_marketing/campaigns/">Campaigns</a></li>
                        <li <?php if ( $template->v('email_marketing/subscribers') ) echo 'class="active"'?>><a href="/email_marketing/subscribers/">Subscribers</a></li>
                        <li <?php if ( $template->v('email_marketing/email_lists') ) echo 'class="active"'?>><a href="/email_marketing/email-lists/">Email Lists</a></li>
                        <li <?php if ( $template->v('email_marketing/autoresponders') ) echo 'class="active"'?>><a href="/email_marketing/autoresponders/">Autoresponders</a></li>
                        <li <?php if ( $template->v('email_marketing/settings') ) echo 'class="active"'?>><a href="/email_marketing/settings/">Settings</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ( $this->account->shopping_cart || $user->has_permission( User::ROLE_STORE_OWNER ) ): ?>
                <li class="sub-menu">
                    <a href="javascript:;" <?php if ( $template->v('shopping-cart') ) echo 'class="active"'?>>
                        <i class="fa fa-shopping-cart"></i>
                        <span>Shopping Cart</span>
                    </a>
                    <ul class="sub">
                        <li <?php if ( $template->v('shopping-cart/orders') ) echo 'class="active"'?>><a href="/shopping-cart/orders/">Orders</a></li>
                        <li <?php if ( $template->v('shopping-cart/users') ) echo 'class="active"'?>><a href="/shopping-cart/users/">Users</a></li>
                        <li <?php if ( $template->v('shopping-cart/shipping') ) echo 'class="active"'?>><a href="/shopping-cart/shipping/">Shipping</a></li>
                        <li <?php if ( $template->v('shopping-cart/coupons') ) echo 'class="active"'?>><a href="/shopping-cart/coupons/">Coupons</a></li>
                        <li <?php if ( $template->v('shopping-cart/settings') ) echo 'class="active"'?>><a href="/shopping-cart/settings/">Settings</a></li>
                    </ul>
                </li>
            <?php endif; ?>

        </ul>
        <!-- sidebar menu end-->
    </div>
</aside>
<!--sidebar end-->
<!--main content start-->
<section id="main-content">
<section class="wrapper site-min-height">

