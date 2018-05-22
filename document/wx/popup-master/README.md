# popup
好用的webapp弹出层

# 参数
```javascript
var settings = {
        close: "remove",// remove | hide | function 关闭popup执行的真正函数,close相当于关闭弹出的函数别名
        title: "", // string | jq对象
        content: "", // string | jq对象
        button: [//[]空数组时没有按钮
            {
                "text": "是",
                "action": function (event, popup, button, index) {
                    popup.close()
                }
            },
            {
                "text": "否",
                "action": function (event, popup, button, index) {
                    popup.close()
                }
            }
        ],
        width: " 90%", //"300px" | 50% 设置主体宽度度
        height: "auto", //"300px" | 50%\ auto 设置主体高度（不包括头和脚）
        fullScreen: false, // true | false 全屏显示
        modal: false, //true 模态 | false 非模态（点击遮罩不移除弹出层）
        className: "",//添加自定义样式名
        callback: {
            "clickCallbackExample": function (event, popup) {//在属于popup内部dom上自定义data-callback="clickCallbackExample"属性,点击后会回调执行这里
            }
        }
    }
    
```
