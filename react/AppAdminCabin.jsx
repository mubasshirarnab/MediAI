(function(){
  const { AdminCabinManagement } = window.Components;
  function AppAdminCabin() {
    return React.createElement(AdminCabinManagement);
  }
  window.Apps = window.Apps || {};
  window.Apps.AppAdminCabin = AppAdminCabin;
})();
