<!-- start Simple Custom CSS and JS -->
<script type="text/javascript">



jQuery(document).ready(function ($) {
  
  const urlRegisterScene  = 'https://backend.experiencemclatam.com/api/register-scene';
  const urlRegisterClicks = 'https://backend.experiencemclatam.com/api/register-clicks';
  const urLogin           = 'https://backend.experiencemclatam.com/api/register-or-login';
  
  const loginUser = () => {
    if (!user_email){
        return;
    }
    const data = {
      email: user_email,
	  username: user_user_name,
	  fullname: user_first_name
  	};
    
    if (user_last_name){
        data.fullname += " "+user_last_name
    }
    
    jQuery.ajax({
      url: urLogin,
      type: "POST",
      data: data,
      success: function (response) {
        console.log(response);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error('Error... ', textStatus, errorThrown);
      }
  	});

    console.log(data);
  }

  loginUser();
  const dd = document.querySelector('.ipanorama[data-json-src]');
  if (!dd){
      return;
  }
  const urlData = dd.getAttribute('data-json-src');

  let scenes = {
    "966E88410D": { 'name': "Fachada"},
    "DB8C51BD0B": { 'name': "Fachada Siguiente Paso"},      
    "684AB0EEDA": { 'name': "Ingreso"},
    "0A223ED019": { 'name': "Entrada"},
    "0DD10F790B": { 'name': "Loby"},
    "A672C0C6FC": { 'name': "Loby Siguiente Paso"},
    "4FC07C3DF9": { 'name': "Final del Corredor"},
    "41536685B1": { 'name': "Punto P&M"},
    "90862BCE2A": { 'name': "Stand UPB"},
    "B348528B0C": { 'name': "Interno Stand UPB"},
    "5442AA524D": { 'name': "Stand Sony"},
    "A25F32ED08": { 'name': "Stand Sony Interno"},
    "9B9AFB5A90": { 'name': "Stand ADZMIC"},
    "4C12A23FBF": { 'name': "Stand ADZMIC Interno"},
    "439DBD9FE2": { 'name': "Stand Vtex"},
    "DD88B0057D": { 'name': "Stand Vtex Interno"},
    "1EF490BAB9": { 'name': "Stand Disponible"},
    "852F52468C": { 'name': "Stand Disponible Interno"},
    "326FF03AF8": { 'name': "Stand Publimetro"},
    "E840E8185B": { 'name': "Stand Publimetro Interno"},
    "9E0BC4DC2F": { 'name': "Stand Pluto"},
    "C000E29E06": { 'name': "Stand Pluto Interno"},
    "E45BC887F6": { 'name': "Stand Sun Media"},
    "F51FCF7C0B": { 'name': "Stand Sun Media Interno"},
    "40E41C4698": { 'name': "Stand Oracle"},
    "030D6A2214": { 'name': "Stand Oracle Interno"},
    "1E72CCD3B7": { 'name': "Stand Agora"},
    "E8D3A4B2F4": { 'name': "Stand Agora Interno"},
    "4E1878A59E": { 'name': "Stand Ads Movil"},
    "AC1870197A": { 'name': "Stand Ads Movil Interno"},
    "E8F6F87084": { 'name': "Stand Tap Tap"},
    "C0BF206754": { 'name': "Stand Tap Tap Interno"},
  };
  let markers = {};

  jQuery.ajax({
    url: urlData,
    type: "GET",
    success: function (response) {
      response.scenes.forEach(scene => {
        if (!scenes[scene.id]) {
          console.error(`Falta el nombre de la escena: ${scene.id}`);
        } else {
          if (scene.userData) {
            try {
              scene.userData = JSON.parse(scene.userData);
            } catch (e) {
              console.log(e);
            }
          }
          Object.assign(scenes[scene.id], scene);
          scene.markers.forEach(marker => {
            if (!marker.title) {
              console.error(`Falta el titulo del marcador ${marker.id}`);
            }
            marker['currentScene'] = scene.id;
            if (marker.userData) {
              try {
                marker.userData = JSON.parse(marker.userData);
              } catch (e) {
                console.log(e);
              }
            }
            markers[marker.id] = marker;
          });
        }
      });
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error('Error... ', textStatus, errorThrown);
    }
  });

  window.onChangeScene = (e) => {
    if (!user_email) {
      return;
    }

    const marker = getMarker(e.cfg.id);
    const currentScene = getScene(marker.currentScene);

    if (e.cfg.linkSceneId) {
      const nextScene = getScene(marker.linkSceneId);
      const dataScene = {
        email: user_email,
        go_scene: nextScene.name,
        nameScene: currentScene.name,
        click_name: marker.title,
        points: 0
      };

      if (nextScene.userData) {
        if (nextScene.userData.points){
            dataScene.points = nextScene.userData.points
        }
      }

      registerDataDB(dataScene, urlRegisterScene);
    } else {
      const dataScene = {
        email: user_email,
        go_scene: null,
        nameScene: currentScene.name,
        click_name: marker.title,
        points: 0
      };
      
      if (marker.userData) {
        if (marker.userData.points){
        	dataScene.points = marker.userData.points
        }
      }

      registerDataDB(dataScene, urlRegisterClicks);
    }
  }

  window.onSetScene = (e, o) => {
    if (!user_email) {
      return;
    }
    const currentScene = getScene(e.$sceneActive[0].dataset.sceneId);
    const nextScene = getScene(o.id);

    const dataScene = {
      email: user_email,
      go_scene: nextScene.name,
      nameScene: currentScene.name,
      click_name: nextScene.name,
      points: 0
    };

    if (nextScene.userData) {
      if (nextScene.userData.points){
          dataScene.points = nextScene.userData.points
      }
    }
    registerDataDB(dataScene, urlRegisterScene);
  }

  const getMarker = (idMarker) => {
    return markers[idMarker];
  }

  const getScene = (idScene) => {
    return scenes[idScene];
  }
  
  
    
  jQuery('.agenda-programa').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Agenda/Programa", 50 ) });
  jQuery('.suscribase').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Suscribase a P&M", 200 ) });
  jQuery('.informacion').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Información del evento", 30 ) });
  jQuery('.preventa').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Preventa P&M", 250 ) });
  jQuery('.registrateiab').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Regístrate para más info de IAB", 200 ) });
  jQuery('.women').on('click', () => { onSetScene( "684AB0EEDA", 'Ingreso', "Conoce las Women to Watch 2021", 100 ) });
  
  
  
  const onSetScene = ( idScene, nameScene = '', click_name = '', points = 0 ) => {
  	if (!user_email) {
      return;
    }
   
    const currentScene = getScene(idScene);

    if ( currentScene ) {    
      const dataScene = {
        email: user_email,
        go_scene: null,
        nameScene: nameScene,
        click_name: click_name,
        points: points
      };   

      registerDataDB( dataScene, urlRegisterScene );
    }
    
  }
  

  
  const registerDataDB = (dataMarker, url) => {
    //console.log('data: ', dataMarker);

    if (dataMarker.email) {
      jQuery.ajax({
        url: url,
        type: "POST",
        data: dataMarker,
        success: function (response) {
          console.log('Register... ', response);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log('Error... ', textStatus, errorThrown);
        }
      });
    }
  }
});</script>
<!-- end Simple Custom CSS and JS -->
