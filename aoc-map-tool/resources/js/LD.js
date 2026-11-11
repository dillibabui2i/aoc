const LD = {
  SDK_KEY: "627ab22c30af0f1476de8281",

  /**
   *
   * @param {*} $flagKey Flag Key name
   * @param {*} onTrue Pass callback function with code what you need to execute when the flag is TRUE
   * @param {*} onFalse Pass callback function with code OR Black what you need to execute when the flag is FALSE
   */
  checkFeatureFlag: function ($flagKey, onTrue, onFalse) {
    // Default Logged in user data
    const d = JSON.parse(loggedInUser);
    const user = {
      key: d.email,
      name: d.name,
      email: d.email,
    };

    // Checking the the Flag here
    const ldClient = LDClient.initialize(LD.SDK_KEY, user);
    ldClient.on("ready", () => {
      const flagValue = ldClient.variation($flagKey, false);
      if (flagValue) {
        onTrue();
      } else {
        onFalse();
      }
    });
  },
};
