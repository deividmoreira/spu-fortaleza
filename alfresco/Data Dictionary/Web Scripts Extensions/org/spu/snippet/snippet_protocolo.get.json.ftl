<#if protocolo != "">
<#if protocolo.properties.title != "">
<#assign sigla = protocolo.properties.title>
<#else>
<#assign sigla = protocolo.name>
</#if>
<#if protocolo.properties.description?exists && protocolo.properties.description != "">
<#assign descricao = protocolo.properties.description>
<#else>
<#assign descricao = protocolo.properties.title>
</#if>
<#if protocolo.parent.type != "{extension.spu}protocolo">
<#assign parentId = ''>
<#else>
<#assign parentId = protocolo.parent.nodeRef>
</#if>
"noderef":"${protocolo.nodeRef}"
,"nome":"${sigla}"
,"parentId":"${parentId}"
,"descricao":"${descricao}"
	<#if protocolo.properties['spu:protocolo.Orgao']?exists>
	,"orgao":"${protocolo.properties['spu:protocolo.Orgao']}"
	<#else>
	,"orgao":""
	</#if>
	<#if protocolo.properties['spu:protocolo.Lotacao']?exists>
	,"lotacao":"${protocolo.properties['spu:protocolo.Lotacao']}"
	<#else>
	,"lotacao":""
	</#if>
	<#if protocolo.properties['spu:protocolo.RecebePelosSubsetores']?exists>
	,"recebePelosSubsetores":"${protocolo.properties['spu:protocolo.RecebePelosSubsetores']?string(1, 0)}"
	<#else>
	,"recebePelosSubsetores":""
	</#if>
	<#if protocolo.properties['spu:protocolo.RecebeMalotes']?exists>
	,"recebeMalotes":"${protocolo.properties['spu:protocolo.RecebeMalotes']?string(1, 0)}"
	<#else>
	,"recebeMalotes":""
	</#if>
<#else>
"noderef":""
,"nome":""
,"parentId":""
,"descricao":""
,"orgao":""
,"lotacao":""
,"recebePelosSubsetores":""
,"recebeMalotes":""
</#if>