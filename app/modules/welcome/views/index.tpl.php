<h3>Get请求</h3>
<ol>
	<li><a href="/welcome/helloworld" target="_blank">Hello World</a></li>
	<li><a href="/welcome/helloworld?test=resumable_exception" target="_blank">Hello World，有可回复的异常</a>
		<ul><li>界面回显，并显示异常消息</li><li>界面回显并没有重新请求</li></ul></li>
	<li><a href="/welcome/helloworld?test=unresumable_exception" target="_blank">Hello World, 有不可回复的异常</a>
		<ul><li>进入异常显示界面</li><li>后退回到原来界面</li></ul></li>
	<li><a href="/welcome/helloworld.json" target="_blank">返回json格式</a></li>
	<li><a href="/welcome/helloworld.xml" target="_blank">返回xml格式</a></li>
</ol>

<h3>POST请求</h3>
<ol>
	<li><a href="/welcome/form" target="_blank">带验证的表单，验证失败表单数据回显</a></li>
	<li><a href="/welcome/form?test=modify" target="_blank">修改表单，验证失败表单数据回显</a></li>
	<li>ajax 访问表单</li>
</ol>