$(function () {

    //统一菜单和内容区的高度
    function calcHeight() {
        var maxHeight = Math.max($(".layout-menu").height(), $(".layout-content").height());
        $(".layout-menu").css("minHeight", maxHeight + "px");
        $(".layout-content").css("minHeight", maxHeight + "px");
    }

    setTimeout(function () {
        calcHeight();
    }, 500)


    //计算内容区宽度
    function calcWidth() {
        $(".layout-content").outerWidth($(".layout-content").parent().width() - $(".layout-menu").outerWidth() + "px");
    }

    calcWidth();

    //随页面动态调整
    $(window).resize(calcWidth);

});


