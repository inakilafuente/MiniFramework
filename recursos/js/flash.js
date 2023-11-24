function animacion()
{
document.write('<applet name=apop code="apPopupMenu" archive="../../../recursos/java/apPopupMenu.jar" height="22" width="100%">');
//document.write('<applet Code=apPopupMenu.class Width="100%" Height="23">');
document.write('<param name="Copyright" value="Apycom Software - www.apycom.com" />');
document.write('<param name="isHorizontal" value="true" />');
document.write('<param name="buttonType" value="0" />');
document.write('<param name="3dborder" value="true" />');
document.write('<param name="solidArrows" value="false" />');
document.write('<param name="systemSubFont" value="false" />');
document.write('<param name="backColor" value="E8E8E8" />');
document.write('<param name="backHighColor" value="E8E8E8" />');
document.write('<param name="fontColor" value="000000" />');
document.write('<param name="fontHighColor" value="FF00FF" />');
document.write('<param name="font" value="Arial,10,1" />');
document.write('<param name="loadingString" value="Cargando Menu..." />');
document.write('<param name="popupOver" value="false" />');
document.write('<param name="menuItemsFile" value="../../../recursos/java/menu_admin.txt" />');
document.write('</applet>');
}
